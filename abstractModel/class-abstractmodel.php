<?php
/**
* @package DB
* @subpackage model
* @author Jonathan Gotti <jgotti at jgotti dot org>
* @licence LGPL
* @since 2007-10
* @changelog - 2008-03-31 - add user define onBeforeSave methods to be called before save time. save is aborted if return true
*                         - methods append_relName || appendRelName to add related object to hasMany relations
*                         - methods set_relName_collection || setRelNameCollection to set an entire modelCollection as a hasMany relation
*                         - method save on modelCollection
*                         - don't load unsetted related object at save time
* @changelog - 2008-03-30 - separation of modelGenerator class in its own file
*                         - remove the withRel parameter everywhere (will be replaced with dynamic loading everywhere)
*                         - replace old relational defs (one2*) by hasOne and hasMany defs instead
* @changelog - 2008-03-25 - some change in modelCollection and apparition of modelCollectionIterator.
*                           now models can be setted with only their PK and be retrieved only on demand (dynamic loading)
* @changelog - 2008-03-24 - now you can have user define methods for setting and filtering datas
*                         - new methods filterDatas, appendFiltersMsgs and hasFiltersMsgs (to ease the creation of user define filter methods)
*                         - getFiltersMsgs can now take a parameter to reset messages
* @changelog - 2008-03-23 - better model generation :
*                           * support autoMapping
*                           * can overwrite / append / or skip existing models
*                           * can set a constant as dbConnectionStr
*                         - new class modelCollection that permitt some nice tricks (thanks to SPL)
* @changelog - 2008-03-15 - now can get and add related one2many objects
*
* @todo write something cool to use sliced methods (setSLice attrs in a better way with some default stuff for each models)
* @todo add dynamic filter such as findBy_Key_[greater[Equal]Than|less[equal]Than|equalTo|Between]
*       require php >= 5.3 features such as late static binding and __callstatic() magic method
*       you will have to satisfy yourself with getFilteredInstances() method until that
*
* @todo implement pagination
*
* @todo remove the $withRel parameter everywhere and permit dynamic load instead (thanks to modelCollection that help on this)
*
* @todo redefine relations with more appropriate terminology and logic such as:
*       -  hasOne  => array( 'model'=>modelName, 'localField'=>localField, dependancyType )
*       -  hasMany => array( 'model'=>modelName, 'localField'=>localField, 'modelField' => modelField ,dependancyType )
*       -  hasMany => array( 'model'=>modelName, 'thisRelField'=>fieldThatRefThisPK, 'linkTable' => linkTable, 'relRelField'=> fieldThatRefRelmodelPK ,dependancyType )
*       dependancyTypes can be something like require, requiredBy, ignore and can permit to handle properly cascade saving and more important onDelete actions
*       onDelete can be extend and call the parent::onDelete methods as required
*
* @todo gerer les sauvegardes en cascades des relations one2many
* dans le cas du one2one on referencie une clef etrangere donc:(ex un/des livre(s) a/ont un auteur)
* - l'objet one2one requiert la reference et donc doit sauver la reference avant sa propre sauvegarde
* 	EN FAIT ON DEVRAIT POUVOIR PRÉCISER SI l'OBJET EST REQUIS OU NON (sort of belongTo)
*
* dans le cas d'une relation one2many on referencie des objets qui possendent une clef etrangere sur l'objet (un auteur a plusieurs livres)
* - l'objet ne depend pas des objets qui ont une référence sur lui
* - l'objet doit etre sauvé avant de pouvoir sauver les objets qui en dépendent
* - l'objet doit savoir comment faire réagir les objets qui en dépendent lorsqu'il est supprimé
*   (delete all, avoid own deletion while dependent objects exists, set a flag to indicate that it's not available anymore, ignore)
* - serait interressant de pouvoir limiter le nombre de many d'une facon ou d'une autre, à réflechir.
*
* dans le cas des relations many2many on passe par une table de transition qui referencie d'autres objets (exemples les livres appartiennent ont plusieurs tags/categories)
* 2 cas sont envisageables -> les objets sont interdependants ou au contraire sont indépendant les uns des autres
* là c'est un cas qui merite encore reflexion, géront déjà le reste propel ne sait pas encore gérer ca nous nous y arriverons c'est un but important
*
* @todo typer les données et leur longueur
* - il serait interressant que les classes filles aient connaissance des informations de type et de longueur des champs
*   de facon a typer les variables mais aussi d'en verifier la longueure (<- pas forcement utile au pire c'est tronqué tant pis si les gens font n'importe quoi)
*   ainsi proposé des validations par défaut sur les données et donc créer de nouveau type de données comme par exemple: email, url etc... qui sont des choses réccurentes
*
* @todo OPTIMISER LES DELETES!!!!!
*/

require_once(dirname(__file__).'/class-db.php');


class modelCollectionIterator extends arrayIterator{
	public $modelCollection=null;
	function __construct($modelCollection){
		$this->modelCollection = $modelCollection;
		parent::__construct($modelCollection);
	}
	function offsetGet($i){
		return $this->modelCollection->offsetGet($i);
	}
	function current(){
		return $this->modelCollection->offsetGet($this->key());
	}
}

/**
* modelCollection is an arrayObject that permit to easily retrieve values from all models in the collection
*/
class modelCollection extends arrayObject{
	protected $collectionType = 'abstractModel';

	function __construct($collectionType=null,array $modelList=null){
		if(empty($modelList))
			$modelList = array();
		parent::__construct($modelList,0,'modelCollectionIterator');
		if(! is_null($collectionType) )
			$this->collectionType = $collectionType;
	}
	function getIterator(){
		return new modelCollectionIterator($this);
	}
	function append($value){
		if(! $value instanceof $this->collectionType)
			throw new Exception("modelCollection::$this->collectionType can only append $this->collectionType models");
		$index=$value->PK;
		return $this->offsetSet($index, $value);
	}
	function offsetSet($index,$value){
		if(! $value instanceof $this->collectionType ){
			if($index===null) #- @todo check that value can be a primaryKey for this type of instance
				$index = $value;
			elseif( $index !== $value)
				throw new Exception("modelCollection::$this->collectionType keys must match values primaryKey ($index !== $value->PK)");
			return parent::offsetSet($index, $value);
			#- ~ if(! $value instanceof $this->collectionType)
				#- ~ throw new Exception("modelCollection::$this->collectionType can only have $this->collectionType models");
		}
		if( $index===null)
			$index=$value->PK;
		elseif($index != $value->PK)
			throw new Exception("modelCollection::$this->collectionType keys must match values primaryKey ($index !== $value->PK)");
		return parent::offsetSet($index, $value);
	}

	function offsetGet($index){
		$value = parent::offsetGet($index);
		if( $value instanceof $this->collectionType )
			return $value;

		if( $value != $index)
			throw new Exception("modelCollection::$this->collectionType try to get an offset with an invalid value.");
		$model = abstractModel::getModelInstance($this->collectionType,$value);
		if( $model ===false )
			throw new Exception("modelCollection::$this->collectionType can't get instance for primariKey $value");
		$this->offsetSet($index,$model);
		return $model;
	}

	###--- SPECIFIC MODELCOLLECTION METHODS ---###
	/**
	* return keys of collection (normally same as all primaryKeys)
	*/
	function keys(){
		return array_keys($this->getArrayCopy());
	}

	/**
	* return list of $k properties foreach models in the collection
	* if $k refer to one/many related model(s) then will return a modelCollection
	*/
	function __get($k){
		if($k==='collectionType')
			return $this->collectionType;

		#- we will need infos from all models so load them all at once.
		$this->loadDatas();

		#- check we are not in presence of hasOne related models in which case we return them in a modelCollection
		$hasOne = abstractModel::_getModelStaticProp($this->collectionType,'hasOne');
		if( isset($hasOne[$k]) ){
			$c = new modelCollection($hasOne[$k]['modelName']);
			foreach($this as $mk=>$m){
				$c[] = $m->datas[$hasOne[$k]['localField']];
			}
			return $c;
		}#- @todo miss case where there's no local field set (primaryKey ref on other table

		//'relName'=> array('modelName'=>'modelName','relType'=>'ignored|dependOn|requireBy','foreignField'=>'fieldNameIfNotPrimaryKey'[,'localField'=>'fieldNameIfNotPrimaryKey']),";
		//'relName'=> array('modelName'=>'modelName','linkTable'=>'tableName','linkLocalField'=>'fldName',''=>'linkForeignField'=>'fldName','relType'=>'ignored|dependOn|requireBy'),";

		#- then check for hasMany related models in this case we use tmp modelCollection to get them all at once.
		$hasMany = abstractModel::_getModelStaticProp($this->collectionType,'hasMany');
		if( isset($hasMany[$k]) ){
			$relDef = $hasMany[$k];
			$c =array();
			#- set empty collection for models whith related not already set
			foreach($this as $m){
				if(! $m->isRelatedSet($k))
					$m->{'set'.$k.'Collection'}(new modelCollection($relDef['modelName']));
			}
			$db = abstractModel::getModelDbAdapter($relDef['modelName']);
			$c = new modelCollection($relDef['modelName']);
			if(! empty($relDef['linkTable']) ){
				$lField = $relDef['linkLocalField'];
				$fField = $relDef['linkForeignField'];
				$links = $db->select_rows($relDef['linkTable'],'*',array("WHERE $lField IN (?)",$this->PK));
			}else{
				$lKey        = empty($relDef['localField'])?abstractModel::_getModelStaticProp($this->collectionType,'primaryKey') :$relDef['localField'] ;
				$lField      = $relDef['foreignField'];
				$lTable      = abstractModel::_getModelStaticProp($this->collectionType,'tableName');
				$fTable      = abstractModel::_getModelStaticProp($relDef['modelName'],'tableName');
				$fField      = abstractModel::_getModelStaticProp($relDef['modelName'],'primaryKey');
				$links = $db->select_rows("$fTable","$fField,$lField",array("WHERE $lField IN (?)",$this->{$lKey}));
			}
			if(! $links)
				return $c;
			foreach($links as $link){ #- we can safely append keys as append method avoid duplicate
				$this[$link[$lField]]->{$k}[]=$link[$fField];
				$c[] = $link[$fField];
			}
			return $c;
		}
		$res = array();
		foreach($this as $mk=>$m){
			$res[$mk] = $this[$mk]->$k;
		}
		return $res;
	}

	/**
	* set all models in collection property at once
	*/
	function __set($k,$v){
		$this->loadDatas();
		foreach($this as $mk=>$m){
			if( isset($m->$k) )
				$m->$k = $v;
		}
	}
	/**
	* apply methods to all model in collection at once
	* @return mixed
	* @todo must have a reflection on what to accept or not and (what to return and when)
	*/
	#- ~ function __call($m,$a){
		#- ~
	#- ~ }

	/**
	* allow to load datas for model in collection all at once.
	* (it will also drop deleted object from collection)
	* @param string $withRelated string of related stuffs to load at the same time. multiple values are separated by |
	* @param int    $limit       limit the load to $limit models at a time.
	* @return $this for method chaining
	*/
	function loadDatas($withRelated=null,$limit=0){
		$copy = $this->getArrayCopy();
		if(empty($copy))
			return $this;
		$needLoad=false;
		foreach($copy as $v){
			if( $v instanceof abstractModel){
				if( $v->deleted ) #- drop deleted models
					unset($this[$v->PK]);
				continue;
			}
			$modelLoaded = abstractModel::isLivingModelInstance($this->collectionType,$v,true);
			if(false===$modelLoaded){
				$needLoad[] = $v;
			}else{
				if(! $modelLoaded->deleted)#- drop deleted models
					unset($this[$v]);
				$this[$v] = $modelLoaded;
			}
		}
		if(! empty($needLoad) ){
			# then load all datas at once
			$db = abstractModel::getModelDbAdapter($this->collectionType);
			$tb = abstractModel::_getModelStaticProp($this->collectionType,'tableName');
			$primaryKey = abstractModel::_getModelStaticProp($this->collectionType,'primaryKey');
			if($limit>0)
				$needLoad = array_slice($needLoad,0,$limit);
			$rows = $db->select_rows($tb,'*',array("WHERE $primaryKey IN (?)",$needLoad));
			if( empty($rows) ) #- @todo musn't append so certainly have to throw an exception ??
				return $this;
			foreach($rows as $row){
				$PK = $row[$primaryKey];
				$this[$PK] = abstractModel::getModelInstanceFromDatas($this->collectionType,$row,true,true);
			}
		}

		if(null!==$withRelated){
			$withRelated = explode('|',$withRelated);
			foreach($withRelated as $key)
				$this->{$key}->loadDatas(null,$limit);
		}

		return $this;
	}
	/**
	* return current model
	*/
	function current(){
		if(count($this) < 1)
			return false;
		return $this->getIterator()->current();
	}
	/**
	* create a new abstractModel matching $this->collectionType and append it to the collection
	* @return abstractModel
	*/
	function appendNew(){
		$m = abstractModel::getModelInstance($this->collectionType);
		$this->append($m);
		return $m;
	}

	/**
	* save models inside the collection and reset tmpKey if needed to avoid breaking key integrity
	*/
	function save(){
		$reset = array();
		$copy = $this->getArrayCopy();
		$oldPks = array_keys($copy);
		foreach($copy as $k=>$m){
			# if not an instance mean it has no change and don't need to be saved
			if( $m instanceof abstractModel)
				$m->save();
		}
		#- now update internal keys for better consitency
		$newPks = array_keys($this->getArrayCopy());;
		foreach(array_diff($oldPks,$newPks) as $old){
			$this[$this[$old]->PK] = $this[$old];
			unset($this[$old]);
		}
		return $this;
	}

	/**
	* delete given models inside the collection in one call
	* @param mixed $PK delete one or multiple at once (null will delete all)
	* @return $this for method chaining
	*/
	function delete($PK=null){
		if( null===$PK)
			$PK = $this->PK;

		if(is_array($PK)){
			foreach($PK as $pk)
				$this->delete($pk);
			return $this;
		}

		$this[$PK]->delete();
		unset($this[$PK]);

		return $this;
	}
}

abstract class abstractModel{
	/**
	* internal pointer to datas
	*/
	protected $datas = array();

	/**
	* list of filters used as callback when setting datas in fields.
	* this permitt to automate the process of checking datas setted.
	* array('fieldName' => array( callable filterCallBack, array additionalParams, str errorLogMsg, mixed $errorValue=false);
	* 	minimal callback prototype look like this:
	* 	function functionName(mixed $value)
	* 	callback have to return the sanitized value or false if this value is not valid
	* 	logMsg can be retrieved by the metod getFiltersMsgs();
	* 	additionnalParams and errorLogMsg are optionnals and can be set to null to be ignored
	* 	(or simply ignored but only if you don't mind of E_NOTICE as i definitely won't use the @ trick)
	*   $errorValue is totally optional and permit to specify a different error return value for filter than false
	*   (can be usefull when you use filter_var to check boolean for example)
	* )
	*/
	static protected $filters = array();
	/**
	* list of error messages returned by filters
	*/
	protected $filtersMsgs = array();
	#- set this on true to bypass filters when required (modelCollection use at loadDatas() time)
	public $bypassFilters = false;

	/**
	* specify one to one relations between models
	*/
	static protected $hasOne = array(
		// 'relName'=> array('modelName'=>'modelName','localField'=>'fieldName','foreignField'=>'fieldName','relType'=>'ignored|dependOn|requireBy')
	);
	/**
	* specify one to many relations between models
	*/
	static protected $hasMany = array(
		// 'relName'=> array('modelName'=>'modelName','localField'=>'fldName','foreignField'=>'fldName','relType'=>'ignored|dependOn|requireBy',
		//                   ['linkTable'=>'tableName','linkLocalField'=>'fldName',''=>'linkForeignField'=>'fldName']])
	);

	/* internal pointer to hasOne related models */
	protected $_oneModels = array();
	/* internal pointer to hasMany related models (modelCollections) */
	protected $_manyModels = array();

	/**
	* each model has it's own pointer to the database
	*/
	protected $dbAdapter = null;
	protected $dbConnectionDescriptor = null;

	/** used to know if save is required(1) or in progress(-1) */
	protected $needSave = 0;
	protected $deleted  = false;

	static protected $modelName = 'abstractModel';
	/**
	* the table name in database
	*/
	static protected $tableName = '';
	/**
	* name of the field used as a primary key
	*/
	static protected $primaryKey = 'id';

	/**
	* will keep trace of each model instances to permit uniques instances
	* of any models.
	*/
	static protected $instances = array();
	/**
	* just a place to keep some various internal datas to avoid of preparing them more than once
	* (for exemples some regexps inside magic methods)
	*/
	static private $_internals = array();

	/**
	* use dbProfiler to encapsulate db instances (used for debug and profiling purpose)
	*/
	static public $useDbProfiler = true;

	/**
	* only for debug purpose
	* @todo delete this debug method
	*/
	static public function showInstances($compact=false){
		if(! $compact)
			return show(self::$instances);
		foreach(self::$instances as $model=>$instances){
			$res[$model] = array_keys($instances);
		}
		show($res);
	}
	/**
	* create an instance of model.
	* @param str  $PK       if given retrieve the datas for the given primary key object.
	*                       else return a new empty model object.
	* @param bool $fullLoad if true then will load all related object at construction time.
	*
	*/
	protected function __construct($PK=null){
		$this->dbAdapter = db::getInstance($this->dbConnectionDescriptor);
		if( self::$useDbProfiler )
		$this->dbAdapter = new dbProfiler($this->dbAdapter);
		$primaryKey = self::_getModelStaticProp($this,'primaryKey');
		if( $PK !== null){
			$this->datas = $this->dbAdapter->select_single_to_array(self::_getModelStaticProp($this,'tableName'),'*',array("WHERE $primaryKey = ?",$PK));
			#- ~ foreach($this->datas as $k=>$v){
				#- ~ self::setModelDatasType($this,$k,$this->datas[$k]);
			#- ~ }
		}else{
			$this->datas[$primaryKey] = uniqid('abstractModelTmpId',true);
		}
		#- then set some internalDatas for further access
		if( empty(self::$_internals[get_class($this)]) ){
			$oneKeys = $manyKeys = $datasKeys = array();
			#- prepare related keys exp
			foreach(array_keys(self::_getModelStaticProp($this,'hasOne')) as $k)
				$oneKeys[] = '['.$k[0].strtoupper($k[0]).']'.substr($k,1);
			self::$_internals[get_class($this)]['hasOneKeyExp'] = implode('|',$oneKeys);
			foreach(array_keys(self::_getModelStaticProp($this,'hasMany')) as $k)
				$manyKeys[] = '['.$k[0].strtoupper($k[0]).']'.substr($k,1);
			self::$_internals[get_class($this)]['hasManyKeyExp'] = implode('|',$manyKeys);
			self::$_internals[get_class($this)]['has*KeyExp'] = self::$_internals[get_class($this)]['hasManyKeyExp']
				.((count($oneKeys)&&count($manyKeys))?'|':'')
				.self::$_internals[get_class($this)]['hasManyKeyExp'];
			#- prepare datas keys exp
			foreach(array_keys($this->datas) as $k)
				$datasKeys[] = '['.$k[0].strtoupper($k[0]).']'.substr($k,1);
			self::$_internals[get_class($this)]['datasKeyExp'] = implode('|',$datasKeys);
		}
	}

	/**
	* used internally to permit unique object instance on newly inserted models.
	* @param abstractModel $instance.
	* @private
	*/
	static private function _setInstanceKey(abstractModel $instance,$oldKey=null){
		if($oldKey !== null) # remove temporary key at save time
			unset(self::$instances[strtolower(get_class($instance))][$oldKey]);
		self::$instances[strtolower(get_class($instance))][$instance->PK] = $instance;
		#- then update related models with correct values
	}

	/**
	* return unique abstractModel instance by primary key or a new empty one.
	* @param string $modelName  model name
	* @param mixed  $PK         value of the primary key
	* @return abstractModel of false on error
	*/
	static public function getModelInstance($modelName,$PK=null){
		# check for living instance
		if(null!==$PK){
			$instance = self::isLivingModelInstance($modelName,$PK,true);
			if($instance!==false)
				return $instance;
		}
		$instance = new $modelName($PK);
		if($instance->datas === false)
			return false;
		self::_setInstanceKey($instance);
		return $instance;
	}

	/**
	* return an instance of $modelName and set instance datas.
	* if $datas contains a field with primaryKey it will check first for a living instance (already loaded  not existing in database) of the given datas
	* @param string $modelName
	* @param array  $datas
	* @param bool   $dontOverideIfExists this only make sense if you have the primaryKey field set in datas
	*                                     in this case if true and a living instance (not checked in database but in loaded instances) is found then it will simply return the instance as found
	*                                     else it will set instance datas to the one given. (can be of help to set multiple keys at once)
	* @param bool   $bypassFilters       if true then will bypass datas filtering
	*/
	static public function getModelInstanceFromDatas($modelName,$datas,$dontOverideIfExists=false,$bypassFilters=false){
		# check for living instance
		$primaryKey = self::_getModelStaticProp($modelName,'primaryKey');
		$instance   = false;
		if(isset($datas[$primaryKey])){ #- check for living instance
			$PK = $datas[$primaryKey];
			$instance = self::isLivingModelInstance($modelName,$PK,true);
			if( false!==$instance){
				if($dontOverideIfExists)
					return $instance;
				unset($datas[$primaryKey]);
			}
		}
		if(false === $instance)
			$instance = self::getModelInstance($modelName);

		if(isset($datas[$primaryKey])){
			$oldPK = $instance->PK;
			$instance->datas[$primaryKey] = self::setModelDatasType($modelName,$primaryKey,$datas[$primaryKey]);
			self::_setInstanceKey($instance,$oldPK);
		}
		#- set Datas
		return $instance->_setDatas($datas,$bypassFilters);
	}

	/**
	* set multiples model datas values at once from an array.
	* @param array  $datas          array of key value pair of datas to set.
	*                               unknown keys or keys corresponding to primarykey will just be ignored.
	* @param bool   $bypassFilters  if true then will bypass datas filtering
	* @return $this for method chaining, you can check filtersMsgs after that call to know if there's some errors
  * @note this method is prepend with a '_' to allow you to still have user define setter for an eventual field named fromDatas (who knows you can need it)
 	*/
	public function _setDatas($datas,$bypassFilters=false){
		$datasDefs = self::_getModelStaticProp($this,'datasDefs');
		$primaryKey = self::_getModelStaticProp($this,'primaryKey');
		$filtersState = $this->bypassFilters;
		if($bypassFilters)
			$this->bypassFilters = true;
		foreach($datas as $k=>$v){
			if( (! isset($datasDefs[$k])) || $k===$primaryKey)
				continue;
			$this->$k = $v;
		}
		$this->bypassFilters = $filtersState;
		return $this;
	}

	/**
	* return array of abstractModel instances by primaryKeys
	* @param string $modelName
	* @param array  $PKs primary keys of desired models
	* @return modelCollection indexed by their primaryKeys
	*/
	static public function getMultipleModelInstances($modelName,array $PKs){
		return new modelCollection($modelName,empty($PKs)?$PKs:array_combine($PKs,$PKs));
	}

	/**
	* return multiple instances of modelName that match simple given filter
	* @param string $modelName
	* @param array  $filter    same as conds in class-db methods
	* @return modelCollection indexed by their primaryKeys
	*/
	static public function getFilteredModelInstances($modelName,$filter=null){
		#@todo find a way to avoid use of a tmpModel
		$tableName  = self::_getModelStaticProp($modelName,'tableName');
		$primaryKey = self::_getModelStaticProp($modelName,'primaryKey');
		$db = self::getModelDbAdapter($modelName);
		$PKs = $db->select_col($tableName,$primaryKey,$filter);
		return self::getMultipleModelInstances($modelName,empty($PKs)?array():$PKs);
	}

	/*
	* return modelCollection of modelName where primaryKeys are returned by the SQL query.
	* the SQL query should return only the primaryKeys values.
	* static public function getQueriedModelInstances($modelName,$query){

	}//*/

	static public function getFilteredModelInstancesByField($modelName,$field,$filterType,$args=null){
		static $filterTypes;
		if(! isset($filterTypes) ){
			$filterTypes = array(
				'GreaterThan'      => '>?',
				'GreaterEqualThan' => '>=?',
				'LessThan'         => '<?',
				'LessEqualThan'    => '<=?',
				'Between'          => 'BETWEEN ? AND ?',
				'Like'             => 'LIKE ?',
				'In'               => 'IN (?)',
				'NotIn'            => 'NOT IN (?)',
				'NotLike'          => 'NOT LIKE ?',
				'Null'             => 'IS NULL',
				'NotNull'          => 'IS NOT NULL',
				'Equal'            => '=?',
				'NotEqual'         => '!=?',
			);
		}
		if(! isset($filterTypes[$filterType]) )
			throw new Exception(__class__.'::'.__function__.'() invalid parameter filterType('.$filterType.')  must be one of '.implode('|',array_keys($filterTypes)).'.');
		$field = self::_cleanKey($modelName,'datasDefs',$field);
		if($field === false)
			throw new Exception(__class__.'::'.__function__.'() invalid parameter field('.$field.')  must be one of a valid datas fieldName.');
		if(! is_array($args) )
			$args = array($args);
		if($filterType==='Between')
			$filter = 'WHERE '.$field.' BETWEEN ? AND ?';
		else
			$filter = 'WHERE '.$field.' '.$filterTypes[$filterType];
		if(substr($filterType,-2)==='In')
			$args = array($args);
		array_unshift($args,$filter);

		return abstractModel::getFilteredModelInstances($modelName,$args);
	}

	/**
	* return all instances of modelName in databases and load them all at once
	* @param string $modelName
	* @param string $withRelated string of related stuffs to load at the same time. multiple values are separated by |
	* @param string $orderedBY   an SQL ORDER BY clause, no order by default
	* @return modelCollection indexed by their primaryKeys
	*/
	static public function getAllModelInstances($modelName,$withRelated=null,$orderedBY=null){
		$tableName  = self::_getModelStaticProp($modelName,'tableName');
		$db = self::getModelDbAdapter($modelName);
		$rows = $db->select_rows($tableName,'*',$orderedBY);
		$collection = new modelCollection($modelName);
		if( $rows ===false )
			return $collection;
		foreach($rows as $row)
			$collection[] = self::getModelInstanceFromDatas($modelName,$row,true,true);
		if( null !== $withRelated )
			$collection->loadDatas($withRelated);
		return $collection;
	}

	/**
	* same as getFilteredModelInstaces but return only a slice from the results.
	* It's typically use to create paginated results set when displaying big list of items.
	* the navigation str can be set using abstractModel::_setPagedNav()
	* @param string $modelName
	* @param array  $filter    same as conds in class-db methods
	* @param int    $pageId    the page to return (start at 1)
	* @param int    $pageSize  max number of results by page.
	* @return array(modelCollection,navigationstring,totalrows);
	*/
	static public function getPagedModelInstances($modelName,$filter=null,$pageId=1,$pageSize=10,$withRelated=null){
		$tableName  = self::_getModelStaticProp($modelName,'tableName');
		$db = self::getModelDbAdapter($modelName);
		$rows = $db->select_slice($tableName,'*',$filter,$pageId,$pageSize);
		$collection = new modelCollection($modelName);
		if( $rows === false )
			return array($collection,'',0);
		list($rows,$nav,$total) = $rows;
		foreach($rows as $row)
			$collection[] = self::getModelInstanceFromDatas($modelName,$row,true,true);
		if( null !== $withRelated )
			$collection->loadDatas($withRelated);
		return array($collection,$nav,$total);
	}
	/**
	* set page navigation string for the given model.
	* in fact just a wrapper to model::dbAdapter->set_slice_attrs
	* @see db::set_slice_attrs for more info
	* @return array() sliceAttrs (full attrs)
	*/
	static public function _setModelPagedNav($modelName,$sliceAttrs=null){
		$db = self::getModelDbAdapter($modelName);
		return $db->set_slice_attrs($sliceAttrs);
	}
	
	/**
	* check if there is any living (already loaded) instance of the given model for matching PK
	* @param string $modelName   model name
	* @param mixed  $PK          value of the primary key
	* @param bool   $returnModel set this to true to return living model instead of true on success
	* @return bool or abstractModel if $returnModel is true
	*/
	static public function isLivingModelInstance($modelName,$PK,$returnModel=false){
		if(! isset(self::$instances[strtolower($modelName)][$PK]))
			return false;
		return $returnModel?self::$instances[strtolower($modelName)][$PK]:true;
	}

	/**
	* check if the given PK exists for this model
	* first look in $instances if an instance is already loaded and then look in database
	* to avoid multiple checking of the same PK it will keep trace of valid PK
	* so if you delete the model from database after having call isValidModelPK on it any further call
	* will still to return true even if it's not the case anymore. (you can force checking without cache with third parameter $dontUseCache)
	* @param string $modelName
	* @param mixed  $PK           value of primaryKey to check
	* @param bool   $dontUseCache if true then will really perform the check without using cached from previous call.
	* @return bool
	*/
	static public function existsModelPK($modelName,$PK,$dontUseCache=false){
		static $valids;
		#- already asked for this one
		if(isset($valids[$modelName][$PK])){
			if($dontUseCache)
				unset($valids[$modelName][$PK]);
			else
				return $valids[$modelName][$PK];
		}
		#- not asked or dontUseCache
		if( isset(self::$instances[strtolower($modelName)][$PK]))
			return $valids[$modelName][$PK] = true;

		$tableName  = self::_getModelStaticProp($modelName,'tableName');
		$primaryKey = self::_getModelStaticProp($modelName,'primaryKey');
		$db = self::getModelDbAdapter($modelName);
		$PKExists     = $db->select_value($tableName,$primaryKey,array("WHERE $primaryKey=?",$PK));
		return $valids[$modelName][$PK]=$PKExists?true:false;
	}

	/**
	* return living instance or make it for relationnal objects
	* @param string $relName the key used to define the relation
	* @return abstractModel or modelCollection repending on the type of relation
	*/
	public function getRelated($relName){
		#- hasOne related
		$hasOne = self::_getModelStaticProp($this,'hasOne');
		if(isset($hasOne[$relName])){
			if(! empty($this->_oneModels[$relName]))
				return $this->_oneModels[$relName];
			$relDef = $hasOne[$relName];

			#- check that this is not a relation based on an unsaved primaryKey
			$lcPKField = self::_getModelStaticProp($this,'primaryKey');
			if( empty($relDef['localField']) )
				$relDef['localField'] = $lcPKField;
			# if $this is a newly unsaved object it can't already have any existing related object relying on it's primaryKey so create a new one and return
			if( $relDef['localField'] === $lcPKField && $this->isTemporary() )
				return $this->_oneModels[$relName] = self::getModelInstance($relDef['modelName']);

			$localFieldVal = $this->datas[$relDef['localField']];
			if( (! empty($relDef['foreignField'])) && $relDef['foreignField'] !== self::_getModelStaticProp($relDef['modelName'],'primaryKey') ){
				# foreignKey is not primaryKey so we must get it throught filteredInstances
				$tmpModel = self::getFilteredModelInstances($relDef['modelName'],array("WHERE $relDef[foreignField]=? LIMIT 0,1",$localFieldVal));
				$tmpModel = $tmpModel->current();
			}else{ # foreignKey is primaryKey
				$tmpModel = self::getModelInstance($relDef['modelName'],$localFieldVal);
			}
			if($tmpModel === false) # no related object was found in database create a new one
				$tmpModel = self::getModelInstance($relDef['modelName']);
			return $this->_oneModels[$relName] = $tmpModel;
		}

		#- hasMany related
		$hasMany = self::_getModelStaticProp($this,'hasMany');
		if(isset($hasMany[$relName])){
			if(! empty($this->_manyModels[$relName]))
				return $this->_manyModels[$relName];
			$relDef = $hasMany[$relName];

			#- check that this is not a relation based on an unsaved primaryKey
			$lcPKField = self::_getModelStaticProp($this,'primaryKey');
			if( empty($relDef['localField']) )
				$relDef['localField'] = $lcPKField;
			# if $this is a newly unsaved object it can't already have any existing related object relying on it's primaryKey so return an empty collection
			if( $relDef['localField'] === $lcPKField && $this->isTemporary() )
				return $this->_manyModels[$relName] = new modelCollection($relDef['modelName']);

			$localFieldVal = $this->datas[$relDef['localField']];
			if( empty($relDef['linkTable']) ){
				return $this->_manyModels[$relName] = abstractModel::getFilteredModelInstances(
					$relDef['modelName'],
					array("WHERE $relDef[foreignField] =?",$localFieldVal)
				);
			}else{
				$PKs = $this->dbAdapter->select_col($relDef['linkTable'],$relDef['linkForeignField'],array("WHERE $relDef[linkLocalField]=?",$localFieldVal));
				return $this->_manyModels[$relName] = abstractModel::getMultipleModelInstances($relDef['modelName'],empty($PKs)?array():$PKs);
			}
		}

		throw new Exception(get_class($this)."::getRelated($relName) unknown relation");
	}

	public function isRelatedSet($k){
		return !(empty($this->_oneModels[$k]) && empty($this->_manyModels[$k]));
	}

	###--- MAGIC METHODS ---###
	public function __get($k){
		#- first check primary key
		if( $k === 'PK' )
			return $this->datas[self::_getModelStaticProp($this,'primaryKey')];
		$hasOne = self::_getModelStaticProp($this,'hasOne');
		$hasMany = self::_getModelStaticProp($this,'hasMany');
		#- then check related objects first
		if( isset($hasOne[$k]) || isset($hasMany[$k]) )
			return $this->getRelated($k);

		#- then check for datas values
		if( isset($this->datas[$k]) )
				return $this->datas[$k];
		#- then protected properties (make them kind of read only values)
		if( isset($this->$k) )
			return $this->$k;

		#- nothing left throw an exception
		throw new Exception(get_class($this)."::$k unknown property.");
	}

	/**
	* setter for datas and hasOne relations
	*/
	public function __set($k,$v){
		if($k === 'PK' || $k === self::_getModelStaticProp($this,'primaryKey') ){
			throw new Exception(get_class($this)." primaryKey can not be set by user.");
		}

		#- apply filters
		if(! $this->bypassFilters){
			$v = $this->filterData($k,$v);
			if($v === false)
				return false;
		}

		#- call user defined setter first
		if( method_exists($this,"set$k") ){
			return $this->{"set$k"}($this->bypassFilters?$v:$this->filterData($k,$v));
		}

		$this->needSave = 1;
		$hasOne = self::_getModelStaticProp($this,'hasOne');
		if(isset($hasOne[$k])){
			$relModelName   = $hasOne[$k]['modelName'];
			$thisPrimaryKey = self::_getModelStaticProp($this,'primaryKey');
			$localField   = empty($hasOne[$k]['localField'])? $thisPrimaryKey : $hasOne[$k]['localField'];
			if( is_object($v) ){
				if(! $v instanceof $relModelName)
					throw new Exception(get_class($this)." error while trying to set an invalid $k value(".get_class($v).").");
				$this->_oneModels[$k] = $v;
				if(isset($this->datas[$localField]) && $localField !== $thisPrimaryKey)
					$this->datas[$localField] = $v->PK;
				return $v;
			}
			#- here we deal with a non object value
			#- @todo in fact will be better to only check that the value is an existing key for model and not create an instance
			switch( $hasOne[$k]['relType']){
				case 'dependOn': #- check for data integrity REQUIRED so if we must check in database load the model at this time
					if($this->bypassFilters || self::existsModelPK($relModelName,$v))
						return $this->datas[$localField] = $v;
					else
						throw new Exception(get_class($this)." error while trying to set an invalid $k value($v).");
					break;
				case 'requiredBy': #- as we don't rely on this relation there's no such big deal to be confident in the user to give correct value,
				case 'ignored':    #- at least if datas are really invalid it will trigger a databse error at save time
					if($localField===$thisPrimaryKey)
						throw new Exception(get_class($this)." error while trying to set an invalid $k value($v).");
					return $this->datas[$localField] = $v;
					break;
			}
		}

		#- ~ $hasMany = self::_getModelStaticProp($this,'hasMany');
		/* Is this a good thing to set a collection at once and if yes reflexion on the fact it must be a collection not an array?
		if(isset($hasMany[$k])){
			if(! $v instanceof modelCollection){
				if( is_array($v) ){
					$tmpCollection = new modelCollection($hasMany[$k]['modelName'],$v);
					foreach($v as $m)
						$tmpCollection[]=$m;
					$v = $tmpCollection;
				}
			}
			if(! $v instanceof modelCollection)
				throw new Exception(get_class($this)." error while trying to set an invalid $k collection.");
			$this->_manyModels[$k] = $v;
		}*/

		if(isset($this->datas[$k]))
			return $this->datas[$k] = self::setModelDatasType($this,$k,$v);

		throw new Exception(get_class($this)." trying to set unknown property $k.");

	}

	function __isset($k){
		return isset($this->datas[$k]);
	}

  public function __call($m,$a){
		$className = get_class($this);

		#- manage add methods for hasMany related
		if(preg_match('!^append_?('.self::$_internals[$className]['hasManyKeyExp'].')$!',$m,$match) ){
			$relName = self::_cleanKey($this,'hasMany',$match[1]);
			if($relName===false)
				throw new Exception("$className trying to call unknown method $m with no matching hasMany[$match[1]] definition.");
			$modelCollection = $this->getRelated($relName);
			$model = array_shift($a);
			$hasMany = self::_getModelStaticProp($this,'hasMany');
			if(null===$model)
				$model = self::getModelInstance($hasMany[$relName]['modelName']);
			$modelCollection[] = $model;
			if(isset($hasMany[$relName]['localField']))
				$this->needSave = 1;
			return $this; #- @todo make reflection on what should be return for now i thing taht allowing method chaining can be nice
		}

		#- manage setter methods for hasMany related
		if( preg_match('!set_?('.self::$_internals[$className]['hasManyKeyExp'].')_?[cC]ollection$!',$m,$match) ){
			$relName = self::_cleanKey($this,'hasMany',$match[1]);
			if($relName === false)
				throw new Exception("$className::$m unknown hasMany relation.");
			if( count($a) !== 1 )
				throw new Exception("$className::$m invalid count of parameters");
			$collection = $a[0];
			if(is_array($collection))
				$collection = new modelCollection($relName,$collection);
			elseif(! $collection instanceof modelCollection )
				throw new Exception("$className::$m invalid parameter $collection given, modelCollection expected.");
			$this->_manyModels[$relName] = $collection;
			return $this; #- @todo make reflection on what should be return for now i thing that allowing method chaining can be nice
		}

		#- manage getter methods for related
		if( preg_match('!^get_?('.self::$_internals[$className]['has*KeyExp'].')$!',$m,$match) )
			return $this->getRelated(self::_cleanKey($this,'hasOne',$match[1]));

		#- manage setter/getter for datas ([gs]et_field|[gs]etField) case sensitive
		if( preg_match('!^([gs]et)_?('.self::$_internals[$className]['datasKeyExp'].')$!',$m,$match) ){
			if($match[1]==='get'){
				return $this->datas[self::_cleanKey($this,'datas',$match[2])];
			}else{
				array_unshift($a,self::_cleanKey($this,'datas',$match[2]));
				call_user_func_array(array($this,'__set'),$a);
				return $this;
			}
		}

		#- nothing left throw an exception
		throw new Exception("$className trying to call unknown method $m.");
	}

	###--- FILTER RELATED METHODS ---###
	/**
	* internal method to get exact keys on magic methods call such as getRelName
	* @param string $keyType hasOne|hasMany|datas|datasDefs
	* @return string clean key or false if not find
	* @private
	*/
	static private function _cleanKey($modelName,$keyType,$k){
		if( $keyType === 'datas')
			$keyType = 'datasDefs';
		$datas = self::_getModelStaticProp($modelName,$keyType);
		if( isset($datas[$k]) )
			return $k;
		#- try to lower first char first
		$k = strtolower($k[0]).substr($k,1);
		if( isset($datas[$k]) )
			return $k;
		#- last try to upper first char first
		$k = ucfirst($k);
		if( isset($datas[$k]) )
			return $k;
		return false;
	}

	static public function setModelDatasType($modelName,$key,&$value){
		if($value===null)
			return null;
		$datasDefs = self::_getModelStaticProp($modelName,'datasDefs');
		if(! isset($key) )
			throw new exception((is_object($modelName)?get_class($modelName):$modelName)."::setModelDatasType() $key is not a valid datas key.");
		$type     = $datasDefs[$key]['Type'];
		if( preg_match('!int|timestamp!i',$type))
			$type = 'int';
		elseif(preg_match('!float|real|double!i',$type))
			$type = 'float';
		else
			$type = 'string';
		settype($value,$type);
		return $value;
	}

	/**
	* apply filter to datas fields, as set in $this->filters or any user defined method named filterFieldName.
	* return given filtered value or false if not succeed and then append a filterMsg.
	* @param string $k the field to be set
	* @param string $v the value to be set
	* @return mixed or false in case of error.
	*/
	public function filterData($k,$v){
		$filters = self::_getModelStaticProp($this,'filters');
		#- if no filters define check for a filterField method or simply return value
		if( empty($filters[$k]) )
			return (method_exists($this,"filter$k")?$v = $this->{"filter$k"}($v):$v);
		#- if we go there we have a $this->filter defined for this field so apply it
		if(count($filters[$k]) === 4){
			list($cb,$params,$msg,$errorValue) = $filters[$k] ;
		}else{
			$errorValue = false;
			list($cb,$params,$msg) = $filters[$k] ;
		}
		if( ! empty($params) ){
			array_unshift($params,$v);
			$v = call_user_func_array($cb,$params);
		}else{
			$v = call_user_func($cb,$v);
		}
		if($v===$errorValue){
			$this->appendFilterMsg($msg?$msg:"invalid value($v) given for $k");
			return false;
		}
		return $v;
	}

	/**
	* append a message to the filterMsg stack
	* @return $this for method chaining
	*/
	public function appendFilterMsg($msg){
		$this->filtersMsgs[] = $msg;
		return $this;
	}
	/**
	* return bool
	*/
	public function hasFiltersMsgs(){
		return ! empty($this->filtersMsgs);
	}
	/**
	* return filters error msgs or false if none
	* @param bool $resetMsgs  do exactly what it mean
	* @return array or false
	*/
	public function getFiltersMsgs($resetMsgs=false){
		$msgs = empty($this->filtersMsgs)?false:$this->filtersMsgs;
		if($resetMsgs)
			$this->filtersMsgs = array();
		return $msgs;
	}

	###--- SOME WAY TO DEAL WITH THE MISSING STATIC LATE BINDING (will probably change with PHP >= 5.3 ---###
	/**
	* quick and dirty "hack" to permit access to static methods and property of models
	* waiting for php >= 5.3 late static binding implementation
	*/
	static public function _getModelStaticProp($modelName,$staticProperty){
		if( is_object($modelName) )
			$modelName = get_class($modelName);
		return eval("return $modelName::\$$staticProperty;");
	}
	static public function _makeModelStaticCall($modelName,$method){
		if(func_num_args() <= 2)
			return call_user_func("$modelName::$method");
		$args = func_get_args();
		$args = array_slice($args,2);
		return call_user_func_array("$modelName::$method",$args);
	}
	#- @todo passer dbadapter en static (ou au moins connectionStr)
	static public function getModelDbAdapter($modelName){
		$tmpModel = new $modelName;
		$db = $tmpModel->dbAdapter;
		self::destroy($tmpModel);
		return $db;
	}
	/**
	* check if modelName has some related models definitions.
	* @param string $modelName
	* @param string $relType   check only for related with the given relType (ignored|requiredBy|dependOn)
	* @param bool   $returnDef if true return an array(hasOne=>array(relName => array relDef),hasMany=>array(relName => array relDef))
	* @return bool or array depend on $returnDef value
	*/
	static public function modelHasRelDefs($modelName,$relType=null,$returnDef=false){
		$hasOne  = self::_getModelStaticProp($modelName,'hasOne');
		$hasMany = self::_getModelStaticProp($modelName,'hasMany');
		if( $relType !== null){
			if(! in_array($relType,array('requiredBy','dependOn','ignored'),true))
				throw new Exception("$modelName::hasRelated('$reltype') Invalid value for parameter relType");
			foreach($hasOne as $name=>$def){
				if($def['relType']!==$relType)
					unset($hasOne[$name]);
			}
			foreach($hasMany as $name=>$def){
				if($def['relType']!==$relType)
					unset($hasMany[$name]);
			}
		}
		if( empty($hasOne) && empty($hasMany))
			return $returnDef?array('hasOne'=>$hasOne,'hasMany'=>$hasMany):false;
		if($relType === null)
			return $returnDef?array('hasOne'=>$hasOne,'hasMany'=>$hasMany):true;
	}

	###--- COMMON METHODS ---###
	/**
	* return a count of given model in database table.
	* @param string $modelName model name you want count for
	* @param array  $filters   same as conds in class-db permit you to count filtered models
	* @return int or false on error
	*/
	static public function getModelCount($modelName,$filter=null){
		$tmpObj = new $modelName();
		$tableName = self::_getModelStaticProp($modelName,'tableName');
		$count = $tmpObj->dbAdapter->select_single_value($tableName,'count(*)',$filter);
		return $count===false?0:(int) $count;
	}

	/**
	* optionnal method to let user define it's own primaryKey generation algorythm.
	* (generally used when autoIncrement is not set on the primaryKey)
	* @return primaryKey
	* @private
	* protected function _newPrimaryKey(){}
	*/

	/**
	* optionnal method onBeforeSave to let user define any action to take before the save to start
	* if return true then abort the save process without any warning in the save method.
	* So the user can choose to throw an exception or to append messages to any stack messages or any choice of his own
	* @private
	* protected function onBeforeSave(){}
	*/


	/**
	* save the Model to database. throw an exception on error.
	* @return $this for method chaining. (throw exception on error)
	*/
	public function save(){
		if($this->deleted)
			throw new Exception(get_class($this)."::save($this->PK) Can't save a deleted model");
		$needSave = $this->needSave;
		# exit if already in saving state
		if( $needSave < 0 )
			return $this;
		if( method_exists($this,'onBeforeSave') ){
			$PK = $this->PK;
			$res = $this->onBeforeSave();
			if( $PK !== $this->PK)
				self::_setInstanceKey($this,$PK);
			if( true === $res )
				return $this;
		}
		$this->needSave = -1;
		$datasDefs = self::_getModelStaticProp($this,'datasDefs');

		#- check related models that need to be save before
		$waitForSave = array();
		$linked      = array();
		foreach(self::_getModelStaticProp($this,'hasOne') as $relName=>$relDef){
			switch($relDef['relType']){
				case 'dependOn': #- we dependOn that one so must save it first
					if(! isset($this->_oneModels[$relName])){
						#- if we already have a value set in or a default one we can goes on else throw an exception
						if(! isset($relDef['localField'])){
							throw new Exception(get_class($this)."::save() require $relName to be set."); # must be an object
						}else{
							$default = $datasDefs[$relDef['localField']]['Default'];
							if( $default !== $this->datas[$relDef['localField']] )
								continue; #- value already set to something we consider here that the value was previously set or at least that user have correctly set this
							throw new Exception(get_class($this)."::save() require $relName to be set.");
						}
					}
					$this->getRelated($relName)->save();
					break;
				case 'ignored': #- ignored are saved if foreignField is primaryKey else we save after with requiredBy
					if(! isset($this->_oneModels[$relName]))
						continue; # nothing was set this time and as it's an ignored relation just keep moving
					if( empty($relDef['foreignField']) || $relDef['foreignField'] === self::_getModelStaticProp($relDef['modelName'],'primaryKey') ){
						$this->getRelated($relName)->save();
						break;
					}
				case 'requiredBy': #- current is requiredBy that so will wait to save it
					if(! isset($this->_oneModels[$relName]))
						continue; # nothing was set this time and it's not a dependancy so keep moving
					$waitForSave[] = $relName;
					break;
			}
		}
		foreach(self::_getModelStaticProp($this,'hasMany') as $relName=>$relDef){
			if(! empty($relDef['linkTable']) ){ #- save object that use a link table at the very end
				if( isset($this->_manyModels[$relName]) )
					$linked[$relName] = $relDef;
				continue;
			}
			switch($relDef['relType']){
				case 'dependOn': #- we dependOn that one so must save it first
					if(! isset($this->_manyModels[$relName]) )
						throw new Exception(get_calss($this)."::save() require at least one $relName to be set.");
					$this->getRelated($relName)->save();
					break;
				case 'ignored': #- ignored are saved if after with requiredBy
				case 'requiredBy': #- current is requiredBy that so will wait to save it
					if(! isset($this->_manyModels[$relName]) )
						continue; #- nothing setted so nothing to save even after
					$waitForSave[] = $relName;
					break;
			}
		}

		if( $needSave > 0){
			$datas = $this->datas;
			$PK = $this->PK;
			$primaryKey = self::_getModelStaticProp($this,'primaryKey');
			$tableName = self::_getModelStaticProp($this,'tableName');
			unset($datas[$primaryKey]); # update all but primaryKey
			if(! $this->isTemporary() ){ # update
				if( false === $this->dbAdapter->update($tableName,$datas,array("WHERE $primaryKey=?",$PK)) )
					throw new Exception(get_class($this)." Error while updating (PK=$PK).");
			}else{ # insert
				# check for user define primaryKey generation
				if(! method_exists($this,'_newPrimaryKey') ){ # database manage key generation (autoincrement)
					$this->datas[$primaryKey] = $this->dbAdapter->insert($tableName,$datas);
					if( $this->datas[$primaryKey] === false )
						throw new Exception(get_class($this)." Error while saving (PK=$PK).");
				}else{ # user define key generation
					$datas[$primaryKey] = $nPK = $this->_newPrimaryKey();
					if( $this->dbAdapter->insert($tableName,$datas,false) === false )
						throw new Exception(get_class($this)." Error while saving (PK=$nPK).");
					$this->data[$primaryKey] = $nPK;
				}
				#- reset temporary instance Key
				self::_setInstanceKey($this,$PK);
			}
		}

		#- then save models that weren't saved
		foreach($waitForSave as $relName){
			$this->getRelated($relName)->save();
		}
		#- the linked part may certainly be optimized for better performance but this should work for now
		foreach($linked as $relName=>$relDef){
			$related = $this->getRelated($relName);
			$needOptimize = $this->dbAdapter->delete($relDef['linkTable'],array("WHERE $relDef[linkLocalField]=?",$this->PK));
			foreach($related as $m){
				$m->save();
				$ldata = array(
					"$relDef[linkLocalField]"   => $this->PK,
					"$relDef[linkForeignField]" => $m->PK
				);
				$this->dbAdapter->insert($relDef['linkTable'],$ldata);
			}
			if($needOptimize) #- keep the table clean (this is not for the better performance there's must be a better way of doing)
				$this->dbAdapter->optimize($relDef['linkTable']);
		}

		$this->needSave = 0;
		return $this;
	}

	/**
	* you should call abstractModel::destroy on the model after this one.
	* - will delete requiredBy hasOne and check integrity key on others hasOne
	* - will delete hasMany linkTables entries (where apply) but won't delete related models so if they have to be deleted you must do it on your own
	* - will also delete others requiredBy hasMany (with no linkTable) and check for data integrity on others
	*/
	public function delete(){
		if($this->deleted)
			throw new Exception(get_class($this)."::delete($this->PK) model already deleted");
		if($this->needSave < 0)
			return $this;
		$this->needSave = -1;
		#- check one related objects
		foreach(self::_getModelStaticProp($this,'hasOne') as $relName=>$relDef){
			switch($relDef['relType']){
				case 'requiredBy': #- related require current so we delete it
					$this->{$relName}->delete();
					break;
				case 'ignored':
					#- if we have a default value to set for ignored related we set it else we just ignore it
					if(isset($relDef['foreignDefault']) && (! empty($relDef['foreignField'])) && ! $this->{$relName}->isTemporary()){
						$this->{$relName}->{$relDef['foreignField']} = $relDef['foreignDefault'];
							$this->{$relName}->save();
						}
					break;
				case 'dependOn':
					#- if we depend on related there's no reason that it depend on us so just ignore it
					#- (at least for now perhaps i forgot something there not really sure it's late)
							break;
			}
		}
		#- check many related objects
		foreach(self::_getModelStaticProp($this,'hasMany') as $relName=>$relDef){
			#- first manage thoose who use a linkTable in this case we just delete links
			if( ! empty($relDef['linkTable']) ){
				$this->dbAdapter->delete($relDef['linkTable'],array("WHERE $relDef[linkLocalField]=?",$this->PK));
				continue;
			}
			#- then manage many related with no linkTable
			switch($relDef['relType']){
				case 'requiredBy': #- related require current so we delete it
					$this->{$relName}->delete(); #- delete all one by one to ensure correct integrity
					break;
				case 'dependOn':
					#- if we depend on related there's no reason that it depend on us so just ignore it
					#- (at least for now perhaps i forgot something there not really sure it's late)
					break;
				case 'ignored':
					#- reset ignored related that have default values otherwise we just ignore it
					if(isset($relDef['foreignDefault']) && (! empty($relDef['foreignField']))){
						#- update all at once
						$rels = $this->getRelated($relName)->loadDatas();
						$rels->{$relDef['foreignField']} = $relDef['foreignDefault'];
						$rels->{$relDef['foreignField']}->save();
					}
				break;
			}
		}//*/
		$tableName  = self::_getModelStaticProp($this,'tableName');
		$primaryKey = self::_getModelStaticProp($this,'primaryKey');
		$res = $this->dbAdapter->delete($tableName,array("WHERE $primaryKey=?",$this->PK));
		if($res===false)
			throw new Exception(get_class($this)."::delete() Error while deleting.");
		$this->detach();
	}

	 public function isTemporary(){
		return preg_match('!^abstractModelTmpId!',$this->PK)?true:false;
	}

	/**
	* will save all models that need to be saved in one call (detached object won't be saved)
	*/
	static public function flush(){
		foreach(self::$instances as $arrayInstances){
			foreach($arrayInstances as $i){
				if($i->needSave>0)
					$i->save();
			}
		}
	}
	/**
	* Detach current model instance from abstractModel::$instances.
	* It's primary purpose is to free some space when object is no more used (on destroy or on delete for exemple)
	* if you unset a model but didn't detach it before in fact it will still live in abstractModel::$instances
	* but it can also be used to have multiple instance for the same model with same PK.
	* (not really a good idea but who know perhaps in some case it can be usefull, let me know)
	* @note if you have other variables that point to the same instance of model they will be detached too
	*/
	function detach(){
		$modelName = self::_getModelStaticProp($this,'modelName');
		if( self::isLivingModelInstance($modelName,$this->PK) )
			unset(self::$instances[strtolower($modelName)][$this->PK]);
		return $this;
	}

	/**
	* detach and destruct the given instance.
	* this method was made because it's no use to unset a model if you still have a pointer on it living in abstractModel::$instances
	* @note WARNING : be aware that it will only destroy the given variable so be carrefull if you have other pointer living for the same instance
	*                 at other place in your script they will be detached as if you have called detach on them.
	*                 so if you have multiple vars pointing on the same instance and only want to drop the given one
	*                 you might think about using a simple unset instead of this one (so others vars won't be detach)
	*/
	static function destroy(abstractModel &$modelInstance){
		$modelInstance->detach();
		$modelInstance = null;
	}
}

