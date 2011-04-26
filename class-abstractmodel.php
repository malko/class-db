<?php
/**
* @package class-db
* @subpackage abstractModel
* @author Jonathan Gotti <jgotti at jgotti dot org>
* @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
* @since 2007-10
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
* - 2011-04-27 - some modification in the abstractModel::filterData() method no filterMethods will be prefered over declation in the filters static property
* - 2011-02-21 - no more fatal error when throwing exception in __toString()
* - 2010-12-15 - new parameter optGroup for modelCollecion::htmlOption() method ( by adrien gibrat < adrien dot gibrat at gmail dot com )
* - 2010-12-13 - add modelCollection::hasModel and abstractModel::inCollection methods
* - 2010-11-24 - don't override null values in setModelDatasTypes()
* - 2010-10-20 - make abstractModel::$dbConnectionDescriptor a static variable of BASE_ models and add a method _setDbConnectionDescriptor to BASE_models
* - 2010-09-27 - attempt in abstractmodel::save to set reverse relation on newly saved models
* - 2010-09-17 - on[before|after]save now called even if needSave < 1
*              - add some more fieldName protection
* - 2010-09-16 - now abstractModel::append[New][hasMany] and abstractModel::set[hasMany]Collection force needSave to 1
* - 2010-09-13 - make $_has[One|Many] static property of extended models public as a workaround for buggy get_class_vars scope resolution
* - 2010-08-16 - cleaning exceptions
* - 2010-08-13 - first attempt implementing abstractModel::getFilteredHasMany(propertyName,exp,comparisonOperator)
* - 2010-08-09 - now abstractmodel::_methodExists() manage a third parameter $avoidStatic to make it consider static methods as invalid
*              - abstractmodel::existsModelPK() bug correction on empty PK
* - 2010-07-07 - some memory and speed improvements
* - 2010-06-18 - modelCollection make sort methods php5.3 compliant
*              - onBefore[save|delete] and onAfter[save|delete] are now only called on needSave state > 1
* - 2010-06-10 - abstractmodel add support for onAfter[delete|save] methods
* - 2010-06-09 - abstractmodel::__set() now always return passed value when call a user defined setter for more consistency
* - 2010-05-28 - abstractmodel::__set() bug correction on hasOne assignation
* - 2010-05-21 - abstractmodel::_getModelStaticProp() check for property existence before returning it
* - 2010-05-18 - abstractmodel::_setDatas() now also apply datas that only have a setter method
* - 2010-05-17 - some work made on hasOne relation setting when foreignField is not the primaryKey of related model
* - 2010-05-03 - some eval replacement/optim in modelCollection::filterBy() method
*              - abstractmodel::_initInternals() is now called at modelCollection::init() time.
*              - abstractmodel::_initInternals() now allow override/merge of $hasOne/$hasMany static properties by defining $_hasOne/$_hasMany static properties
* - 2010-04-13 - change modelCollection::remove() method to be chainable or return the removed node (no more boolean return)
* - 2010-04-07 - new method modelCollection::merge
*              - modelCollection::append() now return $this for method chaining and support appending a whole collection
* - 2010-03-24 - change abstractmodel::_setPagedNav() methods to use abstractmodel::$internals
*              - correct typo error when setting internal has*KeyExp
*              - new modelCollection::paged() method
* - 2010-03-16 - add use of db::protect_field_names() method at many places
* - 2009-12-09 - add support for modelAddons::_initModelType
* - 2009-11-26 - replace all isset($this->datas[]) by $this->datasDefs[])
* - 2009-11-23 - move __toString to _toString method for compatibility with php5.3
* - 2009-10-26 - now __toString() allow escaping of % chars by a preceding backslash Or % (\%|%%)
* - 2009-10-01 - new modelCollection::isEmpty() method
* - 2009-09-30 - now abstractmodel::getRelated() check for living instance when getting hasOne relation on foreign unique key
* - 2009-09-29 - bug correction in modelCollection::__get() when getting hasOne relation on foreign unique key
* - 2009-07-08 - new modelCollection::slice() method
* - 2009-07-07 - now [r]sort can sort properties get by user defined getter (abstractmodel::getPropertyName()). (don't work for dynamic methods [r]sortPropertyname)
* - 2009-07-06 - bug correction in modelCollection::__construct() (forgotten continue)
* - 2009-07-02 - bug correction on cascading deletes on hasOne relations that are not set
*              - bug correction on abstractmodel::__get() on null values
*              - modelCollection now check for emptyPK at init time
* - 2009-07-01 - new method abstractmodel::getModelLivingInstances() that return a modelCollection of all instanciated given model
*              - new method abstractmodel::getModelDummyInstance() that does exactly what it says
* - 2009-06-24 - modelCollection::htmlOptions() now use __toString to render label (so supporte more complex expression)
* - 2009-06-23 - bug correction in _setDatas regarding setting unknown keys as collection
* - 2009-06-22 - modelCollection::shuffle() doesn't loadDatas anymore.
* - 2009-06-16 - bug correction on setting hasManyCollection on relations where relName was diffent from related modelName
* - 2009-06-12 - abstractmodel::getPagedModelInstances() suppress warning error on empty results sets
* - 2009-05-28 - now abstractmodel::_setDatas() can also set hasOne and hasMany relations
* - 2009-04-30 - new sortType 'shuffle' and new modelCollection::shuffle method.
*              - bug correction in getFilteredModelInstance() with $_avoidEmptyPK set to true that returned temporary model instead of null on empty results. (back to normal behaviour, returning null)
* - 2009-04-03 - modelCollection::filterBy() partially rewrited to work on hasOne related for all comparison type (only work with primaryKeys when comparing hasOne property)
*              - modelAddons::isModelMethodOverloaded() is now case insensitive
*              - modelCollection::__set() just pass value to models in it with no check at all
* - 2009-04-02 - modelCollection::sortCompare() now will work on hasOne related by using their primaryKey.
* - 2009-03-18 - modelCollection::__toString() take second parameter $separator again
*              - rewrite abstractmodel constructor, getModelInstance, getModelInstanceFromDatas to properly pass primaryKey value to modelAddons constructors (ie: when initialising collection).
* - 2009-03-17 - now abstractmodel::_setDatas() use directly the __set() method to permit call to _setDatas inside user defined setter.
*              - now setting a hasOne relation by primaryKey using __set (so most of common setter methods) will drop any previously loaded related object to ensure data integrity
* - 2009-03-13 - now __toString methods can render expression like %{expression}%
* - 2009-02-09 - new abstractModel statics methods _modelGetSupportedAddons() _modelSupportsAddon()
*              - new abstractModel::supportsAddon() method
* - 2009-02-08 - add forgotten support for optional abstractModel::onBeforeDelete() method
* - 2009-01-26 - new abstractModel::_methodExists method to check method inside current instance and attached modelAddons all at once
* - 2009-01-21 - now modelCollection sum,max,min methods return 0 on empty collection
*              - add PK to dataFields check expression so many dynamic methods are now callable with with PK (ie: modelCollection->sortByPK())
* - 2009-01-15 - new abstractModel static property $_avoidEmptyPK that when setted to true will return make getInstance to work as getNew when called with an empty PK
*              - add abstractModel::__get() accessors to 'dfltFiltersDictionary','modelAddons','__toString','_avoidEmptyPK' static properties
*              - modelCollection::__get() now check $_avoidEmptyPK when accessing models properties
*              - new modelCollection methods getTemporaries() and removeTemporaries()
* - 2008-12-19 - new abstractModel::modelCheckFieldDatasExists()
* - 2008-12-03 - bug correction in abstractController::append[_?hasMany]()
*              - now user defined setters are not called when bypassFilters is on (this is to avoid passing in user setter when loading collection datas)
* - 2008-11-28 - bug correction in int type detection
*              - new abstractModel::_getProperties() method and new parameter $concatSeparator for modelCollection::getPropertiesList()
* - 2008-11-27 - little modification in type detection
*              - new modelCollection::min[_?FieldName]([FieldName]) modelCollection::max[_?FieldName]([FieldName]) methods
* - 2008-11-26 - first attempt to make modelCollection::filterBy() with 'in' || '!in' operator to work with modelCollection as expression
* - 2008-11-18 - make modelCollection sort methods stable (preserve previous order in case of equality)
* - 2008-10-07 - bug correction (typo error in getRelated methods)
* - 2008-09-04 - now abstractModel::__get() will first try to find a user defined getter (ie: get[property])
*              - now modelCollection::__construct() is protected you must use modelCollection::init() instead to try to get user defined collection class first
* - 2008-09-01 - new modelCollection::sum() and modelCollection::avg() methods
*              - modelCollection::__call() now manage (sum|avg)[_]PropertyName() methods
*              - __toString() now use heredoc syntax to permit only one call to eval by model idem for collections (small optimisation)
* - 2008-08-29 - new modelCollection::getPropertyList() method
*              - new modelCollection::getPropertiesList() method
*              - modelCollection::filterBy() and modelCollection::__get() now use modelCollection::getPropertyList()
*              - modelCollection::__call() now manage get[_]PropertyNameList() methods
*              - abstractModel::_cleanKey() now can check multiple keyType at once
* - 2008-08-28 - modelCollection::filterBy() now work on related objects properties
* - 2008-08-27 - bug correction in appendFilterMsgs with langManager support.
*              - modelCollection::__call() now manage map[_]FieldName methods
*              - new abstractModel::getFilteredModelInstance() method
* - 2008-08-20 - now modelCollection::filterBy() support in,!in,IN,!IN operators for in_array comparisons
*              - modelCollection::(in|de)crement() now call modelCollection::loadDatas() first
*              - now setting model datas values will only change needSave state to 1 if set to a new value;
* - 2008-08-19 - new modelCollection dynamic methods, filterBy[_]FieldName and [r]sortBy[_]FieldName
*              - __call now will call each instance methods and return their result as an array if no dynamic method was found
* - 2008-08-12 - new modelCollection::__toString() method
*              - add parameter $formatStr to modelCollection::__toString() and abstractModel::__toString() methods to override default format on explicit call
*              - modelCollection::htmlOptions() now call modelCollection::loadDatas() prior to rendering
* - 2008-08-06 - modelCollection::htmlOptions() will use default model::__toString() method to render empty labels
* - 2008-08-05 - add property and method __toString to abstractModel to ease string representation
* - 2008-07-30 - bug correction in modelCollection::increment/decrement
* - 2008-07-28 - new method modelAddon::isModelMethodOverloaded to test dynamic methods overloading
* - 2008-07-25 - add lookup in modelAddons for filtering methods id none found in the model.
* - 2008-07-23 - modelCollection::htmlOptions() can now take a collection or array as $selected parameter for multiple selection options
* - 2008-05-22 - add optional orderBy for hasManyDef that will be used at getRelated() time (usefull for related datas that must be sort by date for example)
*              - setting related hasOne model by primaryKey will now set the correct type in the model datas array
* - 2008-05-15 - new models method appendNew[HasManyName] that return the new model and link it to current model
*              - add remove method to modelCollection
* - 2008-05-08 - add abstractModel static public property $dfltFiltersDictionary to permit filterMsgs to be lookedUp in dictionaries (specific to simpleMVC)
*              - add sprintf support to appendFilterMsg() method
* - 2008-05-06 - now modelCollection::loadDatas() reset tmpAbstracModel keys
*              - modelCollection now implement method to create htmlOptions from models handles in it
*              - abstractModels can now access some static properties (primaryKey,tableName,modelName) as normal instance properties
* - 2008-05-05 - rewrite modelCollection::current() and add prev(), next(), first() and last() methods
*                all returning abtractModel or null
*              - add new methods support to modelCollection:
*                - increment/decrement methods (in|de)crementPropertyName($step=1)
*                - filtering method filterBy($propertyName,$exp,$comparisonOperator)
*                - mapping method map($callBack,$propertyName=null)
*                - cloning method clonedCollection()
*              - add $leaveNeedSaveState to permit to set instances by datas wihtout setting $needSave to 1
* - 2008-05-04 - add $isDummyInstance internal parameter to abstractModel::__construct
* - 2008-05-02 - some more methods to set datasTypes now addons can load modelInstance datas.
* - 2008-04-30 - now _makeModelStaticCall and getModelDbAdapter can take instance of model as first parameter instead of string
*              - add support for modelAddons
* - 2008-04-25 - new  sort and rsort methods for modelCollection.
* - 2008-04-xx - so many changes that i didn't mentioned them as we weren't in any "stable" or even "alpha" release
*                now that we have a more usable version i will write changes again
* - 2008-03-31 - add user define onBeforeSave methods to be called before save time. save is aborted if return true
*              - methods append_relName || appendRelName to add related object to hasMany relations
*              - methods set_relName_collection || setRelNameCollection to set an entire modelCollection as a hasMany relation
*              - method save on modelCollection
*              - don't load unsetted related object at save time
* - 2008-03-30 - separation of modelGenerator class in its own file
*              - remove the withRel parameter everywhere (will be replaced with dynamic loading everywhere)
*              - replace old relational defs (one2*) by hasOne and hasMany defs instead
* - 2008-03-25 - some change in modelCollection and apparition of modelCollectionIterator.
*                now models can be setted with only their PK and be retrieved only on demand (dynamic loading)
* - 2008-03-24 - now you can have user define methods for setting and filtering datas
*              - new methods filterDatas, appendFiltersMsgs and hasFiltersMsgs (to ease the creation of user define filter methods)
*              - getFiltersMsgs can now take a parameter to reset messages
* - 2008-03-23 - better model generation :
*                * support autoMapping
*                * can overwrite / append / or skip existing models
*                * can set a constant as dbConnectionStr
*              - new class modelCollection that permitt some nice tricks (thanks to SPL)
* - 2008-03-15 - now can get and add related one2many objects
* @todo replace all getModel/setModel methods by modelGet/modelSet to avoid collision with dynamicly defined get/set methods
* @todo add dynamic filter such as findBy_Key_[greater[Equal]Than|less[equal]Than|equalTo|Between]
*       require php >= 5.3 features such as late static binding and __callstatic() magic method
*       you will have to satisfy yourself with getFilteredInstances() or getFilteredInstancesByField() methods until that
* @todo OPTIMISER LES DELETES (notamment sur les collections)!!!!!
* @todo penser a setter les relations quand on fait un setModelCollection!!!!!
*       et aussi au moment du save ca serait pas mal!
*/

require_once(dirname(__file__).'/class-db.php');
/**
* abstract class to ease modelAddons coding
* @class modelAddon
*/
abstract class modelAddon{
	protected $modelInstance = null;
	protected $modelName     = null;
	protected $dbAdapter     = null;
	protected $overloadedModelMethods = array();
	static private $_initializedModelType = array();
	/**
	* create an instance of modelAddon, it receive the modelInstance before any datas settings
	* so it also receive the requested instance primaryKey.
	* if constructor set the primaryKey value of the modelInstance then the model constructor
	* won't try to set any more datas.
	*/
	public function __construct(abstractModel $modelInstance,$instancePK=null){
		$this->modelInstance = $modelInstance;
		$this->modelName     = abstractModel::_getModelStaticProp($this->modelInstance,'modelName');
		$this->dbAdapter     = $this->modelInstance->dbAdapter;
		$className = get_class($this);
		if( method_exists($this,'_initModelType') && ! isset(self::$_initializedModelType["$className:$this->modelName"])){
			$this->_initModelType();
			self::$_initializedModelType["$className:$this->modelName"] = true;
		}
	}

	/**
	* optionnal _initModelType method may be defined and if exists will only be called once at first modelType instanciation
	* it may be used to initialized some overloaded methods once by modelType.
	* protected function _initModelType(){
	*	}
	*/

	/**
	* check if a modelAddon handle or not a given method (sort of method_exists but can handle dynamic methods such as thoose managed by __call (in such case must be declared in self::$overloadedModelMethods);
	* @note to modelAddon developpers: when developping addon with dynamic overloaded methods, please consider using abstractModel::_setData() inside your ovveriden setter
	*       and be sure to pass third parameter ($bypassFilters) to true if you want things to work like you expect. If you don't you'll probably end in unpredictable behaviour (such as infinite loop at setting time for example)
	* @param string $methodname
	* @return bool
	*/
	public function isModelMethodOverloaded($methodName){
		if( method_exists($this,$methodName) )
			return true;
		elseif( (! empty($this->overloadedModelMethods) ) && in_array(strtolower($methodName),array_map('strtolower',$this->overloadedModelMethods)))
			return true;
		return false;
	}

}

/**
* iterrator for modelCollection arrayObject.
* You certainly won't instanciate this on your own please see modelCollection for further infos
* @see modelCollection
* @class modelCollectionIterator
*/
class modelCollectionIterator extends arrayIterator{
	public $modelCollection=null;
	public function __construct($modelCollection){
		$this->modelCollection = $modelCollection;
		parent::__construct($modelCollection);
	}
	public function offsetGet($i){
		return $this->modelCollection->offsetGet($i);
	}
	public function current(){
		return $this->modelCollection->offsetGet($this->key());
	}
}

/**
* modelCollection is an arrayObject that permit to easily work on a whole set of models at once (retrieving/setting values, sort and so on...).
* @class modelCollection
*/
class modelCollection extends arrayObject{
	protected $collectionType = 'abstractModel';
	/** internal properties used at sort time */
	private $_sortBy       = null;
	private $_sortType     = null;
	private $_sortReversed = false;
	private $_datasKeyExp  = '';

	/**
	* Don't use this method anymore, use modelCollection::init() instead.
	* The only reason why this method is still public is that modelCollection is based on arrayObject
	* and so the constructor must be public to permit error free inheritance.
	* @see modelCollection::init()
	* @private
	*/
	public function __construct($collectionType=null,array $modelList=null){
		if(empty($modelList))
			$modelList = array();
		#- ~ parent::__construct($modelList,0,'modelCollectionIterator');
		if(! is_null($collectionType) ){
			$this->collectionType = $collectionType;
			$modelInternals = abstractModel::_initInternals($collectionType,true);
			$this->_datasKeyExp = $modelInternals['datasKeyExp'].'|PK';
		}
		# ensure primaryKey consistency
		$list = array();
		$avoidEmptyPK = abstractModel::_getModelStaticProp($this->collectionType,'_avoidEmptyPK');
		foreach($modelList as $v){
			if($v instanceof $this->collectionType){
				$list[$v->PK] = $v;
				continue;
			}elseif( empty($v) && $avoidEmptyPK ){
				continue;
			}
				$list[$v] = $v;
		}
		parent::__construct($list,0,'modelCollectionIterator');
	}

	/**
	* return a new modelCollection instance of the correct type
	* @param string $modelCollection required modelname of the model we want to init a collection for
	* @param array  $modelList       otpional list of model we want to put in the collection
	*/
	static public function init(){
		$args = func_get_args();
		if(! isset($args[0]) )
			throw new RuntimeException('modelCollection::init() missing required parameter collection type');
		$collectionType = $args[0];
		$modelList = isset($args[1])?$args[1]:null;

		$collectionClassName = $collectionType.'Collection';
		if( class_exists($collectionType) && class_exists($collectionClassName,false) )
			return new $collectionClassName($modelList);
		else
			return new modelCollection('abstractModel',$modelList);
	}

	public function getIterator(){
		return new modelCollectionIterator($this);
	}

	/**
	* append a single collectionType model instance or a full modelCollection of same collectionType
	* @param mixed value abstractModel or modelCollection to append to the current modelCollection
	* @return $this for method chaining
	*/
	public function append($value){
		if( $value instanceof modelCollection && $value->collectionType === $this->collectionType){
			foreach($value->keys() as $i){
				$this[] = $i;
			}
			return $this;
		}
		if(! $value instanceof $this->collectionType)
			throw new InvalidArgumentException("modelCollection::$this->collectionType can only append $this->collectionType models");
		$index=$value->PK;
		$this->offsetSet($index, $value);
		return $this;
	}
	/**
	* create a new abstractModel matching $this->collectionType and append it to the collection
	* @return new abstractModel
	*/
	public function appendNew(){
		$m = abstractModel::getModelInstance($this->collectionType);
		$this->append($m);
		return $m;
	}

	/**
	* remove a model from collection (not a delete)
	* @param mixed $model the model instance to remove or its PK
	*                     or a list/collection of model instance/PK to remove
	*                     if null remove all models from the collection
	* @param  bool $chaining if true then return $this collection instead of removed models collection.
	* @return modelCollection return a collection of removed nodes or $this if $chaining is true
	*/
	public function remove($model,$chaining=false){
		if(! count($this))
			return $chaining?$this:self::init($this->collectionType);
		if( is_null($model) )
			$model = $this->keys();

		if(is_array($model) || $model instanceof modelCollection  ){
			if( $model instanceof modelCollection && $model->collectionType!==$this->collectionType){
				throw new InvalidArgumentException("modelCollection::$this->collectionType Can't remove models that are not $this->collectionType");
			}
			if( $chaining ){
				foreach($model as $m)
					$this->remove($m);
				return $this;
			}else{
				$ret = modelCollection::init($this->collectionType);
				foreach($model as $m){
					$tmp = $this->remove($m);
					if( null !== $tmp)
						$res[]=$tmp;
				}
				return $ret;
			}
		}

		if( $model instanceof $this->collectionType )
			$model = $model->PK;

		if(! isset($this[$model]) )
			return $chaining?$this:null;

		if(! $chaining)
			$ret = $this[$model];
		unset($this[$model]);
		return $chaining?$this:$ret;
	}
	/**
	* remove temporary models in collection.
	* @param  bool $chaining if true then return $this collection instead of removed models collection.
	* @return modelCollection return a collection of removed nodes or $this if $chaining is true
	*/
	public function removeTemporaries($chaining=false){
		if(! $this->count() )
			return $chaining?$this:self::init($this->collectionType);
		return $this->remove($this->getTemporaries()->keys(),$chaining);
	}

	/**
	* check given model is an item of the current collection
	* @see abstractModel::inCollection()
	* @param abstractModel $model
	* @return bool
	*/
	public function hasModel(abstractModel $model){
		if(! $model instanceof $this->collectionType ){
			throw new InvalidArgumentException( get_class($this).'::hasModel() expect a '.$this->collectionType.' as first arguments');
		}
		return isset($this[$model->PK])?true:false;
	}

	/**
	* return new modelCollection of temporaries instance living inside current collection.
	* @return modelCollection
	*/
	public function getTemporaries(){
		if(! $this->count() )
			return self::init($this->collectionType);
		$temps = array_keys(array_filter($this->isTemporary()));
		return self::init($this->collectionType,$temps);
	}


	public function offsetSet($index,$value){
		if(! $value instanceof $this->collectionType ){
			if($index===null) #- @todo check that value can be a primaryKey for this type of instance
				$index = $value;
			elseif( $index !== $value)
				throw new UnexpectedValueException("modelCollection::$this->collectionType keys must match values primaryKey ($index !== $value->PK)");
			return parent::offsetSet($index, $value);
			#- ~ if(! $value instanceof $this->collectionType)
				#- ~ throw new Exception("modelCollection::$this->collectionType can only have $this->collectionType models");
		}
		if( $index===null)
			$index=$value->PK;
		elseif($index != $value->PK)
			throw new UnexpectedValueException("modelCollection::$this->collectionType keys must match values primaryKey ($index !== $value->PK)");
		return parent::offsetSet($index, $value);
	}

	public function offsetGet($index){
		$value = parent::offsetGet($index);
		if( $value instanceof $this->collectionType )
			return $value;

		if( $value != $index)
			throw new OutOfBoundsException("modelCollection::$this->collectionType try to get an offset with an invalid value.");
		$model = abstractModel::getModelInstance($this->collectionType,$value);
		if( !$model instanceof $this->collectionType)
			throw new OutOfBoundsException("modelCollection::$this->collectionType can't get instance for primaryKey $value");
		$this->offsetSet($index,$model);
		return $model;
	}
	/**
	* append the given collection to the current one.
	* @param mixed $collection may be a modelCollection of same modelType or a list of PK for the same model
	* @return new modelCollection
	*/
	public function merge($collection){
		if( (! is_array($collection) ) && ! ($collection instanceof modelCollection && $collection->collectionType===$this->collectionType) ){
			throw new InvalidArgumentException("modelCollection::$this->collectionType invalid merge parameter");
		}
		if( ! is_array($collection)){
			$collection = $collection->keys();
		}
		return modelCollection::init($this->collectionType,array_merge($this->keys(),$collection));
	}

	###--- SPECIFIC MODELCOLLECTION METHODS ---###
	/**
	* return keys of collection (normally same as all primaryKeys)
	*/
	public function keys(){
		return array_keys($this->getArrayCopy());
	}

	/**
	* return list of $k properties foreach models in the collection
	* if $k refer to one/many related model(s) then will return a modelCollection
	*/
	public function __get($k){
		if($k==='collectionType')
			return $this->collectionType;

		#- check we are not in presence of hasOne related models in which case we return them in a modelCollection
		$hasOne = abstractModel::_getModelStaticProp($this->collectionType,'hasOne');
		if( isset($hasOne[$k]) ){
			$c = modelCollection::init($hasOne[$k]['modelName']);
			$avoidEmptyPK = abstractModel::_getModelStaticProp($c->collectionType,'_avoidEmptyPK');
			/*if( empty($hasOne[$k]['localField']) ){ //-- must be in presence of unique key field on foreign table
				return abstractModel::_makeModelStaticCall($hasOne[$k]['modelName'],'getFilteredInstances',array('WHERE '.$hasOne[$k]['foreignField'].' IN (?)',$this->PK));
			}else{
				foreach($this->loadDatas() as $mk=>$m){
					$c[] = ( $avoidEmptyPK && empty($m->datas[$hasOne[$k]['localField']]))?$m->{$k}:$m->datas[$hasOne[$k]['localField']];
				}
			}*/
			/*testing*/
			foreach($this->loadDatas(empty($hasOne[$k]['localField'])?$k:null) as $mk=>$m){
				if(empty($hasOne[$k]['localField'])|| ($avoidEmptyPK && empty($m->datas[$hasOne[$k]['localField']])) ){
					$c[] =$m->{$k};
				}else{
					$c[] = $m->datas[$hasOne[$k]['localField']];
				}
			}
			/*testing*/
			#if( empty($hasOne[$k]['localField']) ){ //-- must be in presence of unique key field on foreign table
			#	#- check related that are not already loaded and preload them if required
			#	$loaded = $this->isRelatedSet($k);
			#	$notLoaded = array_diff_assoc($loaded,array_filter($loaded));
			#	if( count($notLoaded))
			#		abstractModel::_makeModelStaticCall($hasOne[$k]['modelName'],'getFilteredInstances',array('WHERE '.$hasOne[$k]['foreignField'].' IN (?)',array_keys($notLoaded)));
			#	foreach($this as $m){
			#		$c[] = $m->{$k};
			#	}
			#	show($c,'color:green');
			#}else{
			#	$avoidEmptyPK = abstractModel::_getModelStaticProp($c->collectionType,'_avoidEmptyPK');
			#	foreach($this->loadDatas() as $mk=>$m){
			#		$c[] = ( $avoidEmptyPK && empty($m->datas[$hasOne[$k]['localField']]))?$m->{$k}:$m->datas[$hasOne[$k]['localField']];
			#	}
			#}
			return $c;
		}
		#- then check for hasMany related models in this case we use tmp modelCollection to get them all at once.
		$hasMany = abstractModel::_getModelStaticProp($this->collectionType,'hasMany');
		if( isset($hasMany[$k]) ){
			$relDef = $hasMany[$k];
			$c =array();
			#- set empty collection for models whith related not already set
			$this->loadDatas();
			foreach($this as $m){
				if(! $m->isRelatedSet($k))
					$m->{'set'.$k.'Collection'}(modelCollection::init($relDef['modelName']));
			}
			$db = abstractModel::getModelDbAdapter($relDef['modelName']);
			$c = modelCollection::init($relDef['modelName']);
			if(! empty($relDef['linkTable']) ){
				$lField = $relDef['linkLocalField'];
				$fField = $relDef['linkForeignField'];
				$links = $db->select_rows($relDef['linkTable'],'*',array('WHERE '.$db->protect_field_names($lField).' IN (?)',$this->PK));
				$db->freeResults();
			}else{
				$lKey   = empty($relDef['localField'])?abstractModel::_getModelStaticProp($this->collectionType,'primaryKey') :$relDef['localField'] ;
				$lField = $relDef['foreignField'];
				$lTable = abstractModel::_getModelStaticProp($this->collectionType,'tableName');
				$fTable = abstractModel::_getModelStaticProp($relDef['modelName'],'tableName');
				$fField = abstractModel::_getModelStaticProp($relDef['modelName'],'primaryKey');
				$links  = $db->select_rows("$fTable","$fField,$lField",array('WHERE '.$db->protect_field_names($lField).' IN (?)',$this->{$lKey}));
				$db->freeResults();
			}
			if(! $links)
				return $c;
			foreach($links as $link){ #- we can safely append keys as append method avoid duplicate
				$this[$link[$lField]]->{$k}[]=$link[$fField];
				$c[] = $link[$fField];
			}
			return $c;
		}
		#- return list of properties.
		$res = array();
		foreach($this->loadDatas() as $mk=>$m)
			$res[$mk] = $m->$k;
		return $res;
	}

	/**
	* set all models property in collection at once
	*/
	public function __set($k,$v){
		foreach($this->loadDatas() as $mk=>$m)
			$m->$k = $v;
	}

	/**
	* return a list of model properties in collection indexed by model primaryKeys
	* @param string $propertyName can point on any property even related names.
	* @return array
	*/
	public function getPropertyList($propertyName){
		if( $this->count() <1)
			return array();
		$hasOne = abstractModel::_getModelStaticProp($this->collectionType,'hasOne');
		$hasMany = abstractModel::_getModelStaticProp($this->collectionType,'hasMany');
		$res = array();
		foreach($this->loadDatas(( isset($hasOne[$propertyName]) || isset($hasMany[$propertyName]) )?$propertyName:null) as $mk=>$m)
			$res[$mk] = $m->$propertyName;
		return $res;
	}
	/**
	* return a list indexed by model primaryKeys of associative array with each model properties
	* @param mixed $propertiesNames list of propery to get from
	* @param string $concatSeparator if $concatSeparator is passed then will implode each model results using given string as separator
	*/
	public function getPropertiesList($propertiesNames,$concatSeparator=null){
		if( $this->count() <1)
			return array();
		$properties = is_array($propertiesNames)?$propertiesNames:preg_split('![,|;]!',$propertiesNames);
		$loadDatas = array();
		foreach($properties as $p){
			if( abstractModel::_cleanKey($this->collectionType,'hasOne|hasMany',$p) )
				$loadDatas[]=$p;
		}
		$res = array();
		foreach($this->loadDatas(empty($loadDatas)?null:implode('|',$loadDatas)) as $mk => $m)
			$res[$mk] = $m->_getProperties($properties,$concatSeparator);
		return $res;
	}

	/**
	* dynamic methods shorthands such as:
	* - get[_]RelNameList()
	* - (increment|decrement)[_]FieldName($step=1)
	* - filterBy[_]FieldName($matchExp,$comparisonOperator=null)
	* - sortBy[_]FieldName($sortType=null);
	* - map[_]FieldName($callback)
	* if none of the above was found then it will call model methods on all instances in the collection
	* and return their results as an array indexed by instances primarykeys
	* @return mixed
	*/
	public function __call($m,$a){
		#- get list methods
		if( preg_match('!get_?([0-9a-zA-Z_]+?)List$!',$m,$match)){
			$dataKey = abstractModel::_cleanKey($this->collectionType,'hasOne|hasMany|datas',$match[1]);
			if( false!==$dataKey)
				return $this->getPropertyList($dataKey);
		}
		# increments / decrements
		if( preg_match("!^(de|in)crement_?($this->_datasKeyExp)$!",$m,$match) ){
			$dataKey = abstractModel::_cleanKey($this->collectionType,'datas',$match[2]);
			if($match[1]==='de')
				return $this->decrement($dataKey,empty($a[0])?1:$a[0]);
			else
				return $this->increment($dataKey,empty($a[0])?1:$a[0]);
		}
		#- filtering methods
		if( preg_match("!^filterBy_?($this->_datasKeyExp)$!",$m,$match) ){
			$dataKey = abstractModel::_cleanKey($this->collectionType,'datas',$match[1]);
			return $this->filterBy($dataKey,$a[0],empty($a[1])?null:$a[1]);
		}
		#- sorting methods
		if( preg_match("!^(r)?sortBy_?($this->_datasKeyExp)$!",$m,$match) ){
			$dataKey = abstractModel::_cleanKey($this->collectionType,'datas',$match[2]);
			$sortType = empty($a[0])?null:$a[0];
			if( $match[1]==='r')
				$this->rsort($dataKey,$sortType);
			else
				$this->sort($dataKey,$sortType);
			return $this;
		}
		#- map methods
		if( preg_match("!^map_?($this->_datasKeyExp)$!",$m,$match) ){
			$dataKey = abstractModel::_cleanKey($this->collectionType,'datas',$match[1]);
			return $this->map($a[0],$dataKey);
		}
		#- sum methods
		if( preg_match("!^sum_?($this->_datasKeyExp)$!",$m,$match) ){
			$dataKey = abstractModel::_cleanKey($this->collectionType,'datas',$match[1]);
			return $this->sum($dataKey);
		}
		#- avg methods
		if( preg_match("!^avg_?($this->_datasKeyExp)$!",$m,$match) ){
			$dataKey = abstractModel::_cleanKey($this->collectionType,'datas',$match[1]);
			return $this->avg($dataKey,isset($a[0])?$a[0]:null);
		}
		#- min /max methods
		if( preg_match("!^(max|min)_?($this->_datasKeyExp)$!",$m,$match) ){
			$dataKey = abstractModel::_cleanKey($this->collectionType,'datas',$match[2]);
			return $this->{$match[1]}($dataKey);
		}

		#- try model methods if not callable by models then model will throw an exception and that's the expected behavior
		$res = array();
		$this->loadDatas();
		foreach($this as $k=>$instance)
			$res[$k] = call_user_func_array(array($instance,$m),$a);
		return $res;
	}

  ###--- INCREMENT / DECREMENT ---###
	public function increment($propertyName,$step=1){
		$this->loadDatas();
		foreach($this as $k=>$v)
			$this[$k]->{$propertyName}+=$step;
		return $this;
	}
	public function decrement($propertyName,$step=1){
		$this->loadDatas();
		foreach($this as $k=>$v)
			$this[$k]->{$propertyName}-=$step;
		return $this;
	}

	/**
	* allow to load datas for model in collection all at once.
	* (it will also drop deleted object from collection)
	* @param string $withRelated string of related stuffs to load at the same time. multiple values are separated by |
	* @param int    $limit       limit the load to $limit models at a time.
	* @return $this for method chaining
	*/
	public function loadDatas($withRelated=null,$limit=0){
		$copy = $this->getArrayCopy();
		if(empty($copy))
			return $this;
		$needLoad=false;
		foreach($copy as $k=>$v){
			if( $v instanceof abstractModel){
				if( $v->deleted ){ #- drop deleted models
					unset($this[$v->PK]);
				}elseif($k !== $v->PK){ #- ensure key integrity for models that are not temporary anymore
					unset($this[$k]);
					$this[] = $v;
				}
				continue;
			}
			$modelLoaded = abstractModel::isLivingModelInstance($this->collectionType,$v,true);
			if(! $modelLoaded instanceof $this->collectionType){
				$needLoad[] = $v;
			}else{
				if( $modelLoaded->deleted)#- drop deleted models
					unset($this[$v]);
				else
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
			$rows = $db->select_rows($tb,'*',array('WHERE '.$db->protect_field_names($primaryKey).' IN (?)',$needLoad));
			$db->freeResults();
			if( empty($rows) ) #- @todo musn't append so certainly have to throw an exception ??
				return $this;
			foreach($rows as $row){
				$PK = $row[$primaryKey];
				$this[$PK] = abstractModel::getModelInstanceFromDatas($this->collectionType,$row,true,true,true);
			}
			$rows=null;
		}

		if(null!==$withRelated){
			$withRelated = explode('|',$withRelated);
			/*foreach($withRelated as $key)
				$this->{$key}->loadDatas(null,$limit);*/
			$hasOnes = abstractModel::_getModelStaticProp($this->collectionType,'hasOne');
			foreach($withRelated as $key){
				if(! (isset($hasOnes[$key]) && empty($hasOnes[$key]['localField'])) ){
					$this->{$key}->loadDatas(null,$limit);
				}else{ //-- this must be a foreign unique key
					$relLoaded = $this->isRelatedSet($key);
					$relNotLoaded = array_diff_assoc($relLoaded,array_filter($relLoaded));
					if( count($relNotLoaded)){
						$relToLoad = abstractModel::_makeModelStaticCall(
							$hasOnes[$key]['modelName'],
							'getFilteredInstances',
							array('WHERE '.abstractModels::getModelDbAdapter($hasOnes[$key]['modelName'])->protect_field_names($hasOnes[$key]['foreignField']).' IN (?)',array_keys($relNotLoaded))
						)->loadDatas()->datas;
						//show($this,$relNotLoaded,'trace;exit');
						foreach($relToLoad as $rel){
							unset($relNotLoaded[$rel[$hasOnes[$key]['foreignField']]]);
						}
						//show($relNotLoaded);
					}
					foreach($relNotLoaded as $pk=>$v){
						if( !isset($relToLoad[$pk]) )//-- put temporary models as none are found in database will avoid further database query.
							$this[$pk]->{$key} = abstractModel::getModelInstanceFromDatas($hasOnes[$key]['modelName'],array($hasOnes[$key]['foreignField']=>$pk));
					}
				}
			}
		}

		return $this;
	}

	/** check the collection is empty */
	public function isEmpty(){
		return ($this->count()>0)?false:true;
	}
	/** return current model in collection @return abstractModel or null */
	public function current(){
		if( count($this) < 1) return null;
		$m = current($this);
		if( false === $m ) return null;
		return ($m instanceof $this->collectionType )?$m:abstractModel::getModelInstance($this->collectionType,$m);
	}
	/** return next model in collection @return abstractModel or null */
	public function next(){
		if( count($this) < 1) return null;
		$m = next($this);
		if( false === $m ) return null;
		return ($m instanceof $this->collectionType )?$m:abstractModel::getModelInstance($this->collectionType,$m);
	}
	/** return prev model in collection @return abstractModel or null */
	public function prev(){
		if( count($this) < 1) return null;
		$m = prev($this);
		if( false === $m ) return null;
		return ($m instanceof $this->collectionType )?$m:abstractModel::getModelInstance($this->collectionType,$m);
	}
	/** return first model in collection @return abstractModel or null */
	public function first(){
		if( count($this) < 1) return null;
		$m = reset($this);
		//$m = current($this->getArrayCopy());
		if( false === $m ) return null;
		return ($m instanceof $this->collectionType )?$m:abstractModel::getModelInstance($this->collectionType,$m);
	}
	/** return last model in collection @return abstractModel or null */
	public function last(){
		if( count($this) < 1) return null;
		$m = end($this);
		if( false === $m ) return null;
		return ($m instanceof $this->collectionType )?$m:abstractModel::getModelInstance($this->collectionType,$m);
	}
	public function slice($offset,$length=null){
		return modelCollection::init($this->collectionType,array_slice($this->keys(),$offset,$length));
	}

	public function paged($pageId=1,$pageSize=10){
		$total = $this->count();
		if( $total === 0 )
			return array(self::init($this->collectionType),'',0);
		$c = $this->slice($pageSize*($pageId-1),$pageSize);
		$nbpages = ceil($total/max(1,$pageSize));
		$modelPagedNav = abstractModel::_setModelPagedNav($this->collectionType);
		extract($modelPagedNav);

		# start/prev link
		if($nbpages > 1 && $pageId != 1){
			$first = str_replace('%lnk',str_replace('%page',1,$linkStr),$first);
			$prev = str_replace('%lnk',str_replace('%page',$pageId-1,$linkStr),$prev);
		}else{
			$first = str_replace('%lnk',str_replace('%page',1,$linkStr),$firstDisabled);
			$prev = str_replace('%lnk',str_replace('%page',$pageId-1,$linkStr),$prevDisabled);
		}
		# next/end link
		if( $pageId < $nbpages ){
			$last  = str_replace('%lnk',str_replace('%page',$nbpages,$linkStr),$last);
			$next = str_replace('%lnk',str_replace('%page',$pageId+1,$linkStr),$next);
		}else{
			$last  = str_replace('%lnk',str_replace('%page',$nbpages,$linkStr),$lastDisabled);
			$next = str_replace('%lnk',str_replace('%page',$pageId+1,$linkStr),$nextDisabled);
		}

		# pages links
		if(preg_match('!%(\d+)?links!',$formatStr,$m)){
			$nblinks = isset($m[1])?$m[1]:'';
			if(! $nblinks){ # all pages links
				$slideStart = 1;
				$slideEnd   = $nbpages;
			}else{ # range pages link
				$delta      = $nblinks%2?($nblinks-1)/2:$nblinks/2;
				$slideStart = max(1,$pageId - $delta - (($pageId+$delta)<=$nbpages?0: $pageId -($nbpages-$delta)) );
				$slideEnd   = min($nbpages,$pageId + $delta + ($pageId > $delta?0: $delta - $pageId + 1 ) );
			}
			for($i=$slideStart;$i<=$slideEnd;$i++){
				$pageLinks[] = str_replace(
					array('%lnk','%page'),
					array(str_replace('%page',$i,$linkStr),$i),
					($i==$pageId?$curpage:$pages)
				);
			}

			$links = implode($linkSep,$pageLinks);
		}

		$formatStr = str_replace(
			array('%first','%prev','%next','%last','%'.$nblinks.'links','%tot','%nbpages','%page'),
			array($first,$prev,$next,$last,$links,$total,$nbpages,$pageId),
			$formatStr
		);


		return array($c,$formatStr,$total);
	}
	/**
	* save models inside the collection and reset tmpKey if needed to avoid breaking key integrity
	*/
	public function save(){
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
	public function delete($PK=null){
		if( null===$PK){
			$PK = $this->PK;
		}

		if(is_array($PK)){
			foreach($PK as $pk)
				$this->delete($pk);
			return $this;
		}

		$this[$PK]->delete();
		unset($this[$PK]);

		return $this;
	}

	###--- SORTING METHODS ---###
	/**
	* sort collection by given datas property name
	* @param str $sortBy   property to use to sort the collection
	* @param str $sortType type of comparison to use can be one of
	*                      - null (default) will use std, natc or binc depending on property type (as defined in model::datasDefs[property])
	*                      - std  use standard > or < comparison
	*                      - nat  use natural order comparison case sensitive (strnatcmp)
	*                      - natc use natural order comparison case insensitive (strnatcasecmp)
	*                      - bin  use binary string comparison case sensitive(strcmp)
	*                      - binc use binary string comparison case insensitive (strcasecmp)
	*                      - shuffle use to randomize the collection. (used internally by method shuffle)
	*                      - user defined callback function (any callable comparison function (see php::usort() for more info)
	* @return $this for method chaining
	*/
	public function sort($sortBy,$sortType=null){
		if( ! $this->count() )
			return $this;
		$this->_sortBy   = $sortBy;
		$propDef = abstractModel::_getModelStaticProp($this->collectionType,'datasDefs');
		if( empty($propDef[$sortBy]) ){
			#- check for getter for this property
			if(! $this->current()->_methodExists('get'.ucfirst($sortBy),false,true) )
				throw new OutOfBoundsException('Try to sort an unsortable property');
			if( null === $sortType)
				$sortType = 'std';
		}
		#- setting sorttype
		if( is_null($sortType) ){ #- choose a default sortType according to datas type
			$propDef = $propDef[$sortBy];
			if( preg_match('!int|timestamp|float|real|double|date|bool!i',$propDef['Type']) )
				$sortType = 'std';
			elseif( false !== stripos($propDef['Type'],'bin') )
				$sortType = 'binc';
			else
				$sortType = 'natc';
		}elseif( (! in_array($sortType,array('std','nat','natc','bin','binc'),true)) && ! is_callable($sortType)){
			throw new UnexpectedValueException('modelCollection::sort() call with invalid sortType parameter');
		}
		$this->_sortType = $sortType;
		#- ensure datas are loaded to avoid multiple on demand loading
		if( 'shuffle'!== $sortType )
			$this->loadDatas();
		if( method_exists($this,'uasort')){
			$this->uasort(array($this,'sortCompare'));
		}else{
			uasort($this,array($this,'sortCompare'));
		}
		return $this;
	}
	/**
	* same as sort but in reverse order
	* @see modelCollection::sort()
	* @param str $sortBy   property to use to sort the collection
	* @param str $sortType type of comparison to use can be one of see modelCollection::sort() method for more info
	* @return $this for method chaining
	*/
	public function rsort($sortBy,$sortType=null){
		$this->_sortReversed = true;
		$this->sort($sortBy,$sortType);
		$this->_sortReversed = false;
		return $this;
	}
	/**
	* internal method to sort collection
	* @private
	* @see modelCollection::sort(), modelCollection::rsort()
	* @note MUST BE PUBLIC TO GET THIS WORK WITH PHP5.3.2
	*/
	public function sortCompare($_a,$_b){
		if( 'shuffle' === $this->_sortType ) #- shuffle don't need to know real values
			return rand(-1,1);
		$a = $_a->{$this->_sortBy};
		$b = $_b->{$this->_sortBy};
		if($a instanceof abstractModel)
			$a = $a->PK;
		if($b instanceof abstractModel)
			$b = $b->PK;
		if($a == $b){
			#- rely on actual sorting position inside the collection
			$keys = $this->keys();
			$a = array_search($_a->PK,$keys,true);
			$b = array_search($_b->PK,$keys,true);
			return $a<$b?-1:1;
		}
		switch($this->_sortType){
			case 'nat':  $res = strnatcmp($a,$b); break;
			case 'natc': $res = strnatcasecmp($a,$b); break;
			case 'bin':  $res = strcmp($a,$b); break;
			case 'binc': $res = strcasecmp($a,$b); break;
			case 'std':  $res = ($a < $b)?-1:1; break;
			default:
				$res = call_user_func($this->_sortType,$a,$b);
		}
		if( $this->_sortReversed)
			return $res>0?-1:1;
		return $res;
	}

	/**
	* randomize order of elements in collection
	* @return modelCollection $this for method chaining
	*/
	public function shuffle(){
		return $this->sortByPK('shuffle');
	}

	###--- FILTERING METHODS ---###
	/**
	* return a modelCollection that contains only models filtered by the given property that match expression
	* propertyName can be a datas field or a hasOne related model for now you can't filter on a hasMany relation
	* @param string $propertyName           the property on which you want to apply filter
	* @param mixed  $exp                    the value the property must match
	* @param string $comparisonOperator     comparison operator to use default is ===
	*                                       some example of what can be use there: <, >, <=, >=, ==, !=, !==
	*                                       - you can also use 'preg' to filter by using preg_match, in which case
	*                                       $exp must be a valid PCRE regexp
	*                                       - 'in' and '!in' can be used with an array (or modelCollection) as $exp
	*                                       to check that properties are or not in_array/in_collection $exp
	*                                       - 'IN' and '!IN' are like 'in' and '!in' but use a strict comparison
	*                                       (no differences whit in/!in when working on hasOne property)
	* return modelCollection a new modelCollection with only matching elements
	*/
	public function filterBy($propertyName,$exp,$comparisonOperator=null){
		$filtered = array();
		if( $this->count() < 1)
			return modelCollection::init($this->collectionType);

		#- prepare comparisonDatas
		$comparisonDatas = $this->getPropertyList($propertyName);

		$relDefs = abstractModel::modelHasRelDefs($this->collectionType,null,true);
		if( isset($relDefs['hasMany'][$propertyName]) )#-- comparison on related hasMany is not implemented for now
			throw new OutOfBoundsException("modelCollection::filterBy('$propertyName') can't work on a hasMany property");
		if( isset($relDefs['hasOne'][$propertyName]) ){#-- comparison on related hasOne only compare primary keys
			$modelName = $relDefs['hasOne'][$propertyName]['modelName'];
			foreach($comparisonDatas as $k=>$v)
				$comparisonDatas[$k] = ($v instanceof abstractModel?$v->PK:$v);
			if( $exp instanceof abstractModel){
				if(! $exp instanceof $modelName)
					throw new InvalidArgumentException("modelCollection::filterBy('$propertyName') call with invalid \$exp parameter");
				$exp = $exp->PK;
			}elseif($exp instanceof modelCollection){
				if( $exp->collectionType !== $modelName)
					throw new InvalidArgumentException("modelCollection::filterBy('$propertyName') call with invalid \$exp parameter");
				$exp = $exp->keys();
			}elseif(is_array($exp)){
				foreach($exp as $k=>$v){
					if($v instanceof $modelName)
						$v = $v->PK;
					else
						abstractModel::setModelDatasType($modelName,abstractModel::_getModelStaticProp($modelName,'primaryKey'),$v);
					$exp[$k] = $v;
				}
			}else{
				abstractModel::setModelDatasType($modelName,abstractModel::_getModelStaticProp($modelName,'primaryKey'),$exp);
			}
		}

		if( null===$comparisonOperator || '===' === $comparisonOperator || '!==' === $comparisonOperator ){ #- strict comparison
			$isequal = $comparisonOperator === '!=='?false:true;
			foreach($comparisonDatas as $k=>$v){
				$eq = $v===$exp?true:false;
				if( $isequal===$eq)
					$filtered[] = $this[$k];
			}
		}elseif( '==' === $comparisonOperator || '!=' === $comparisonOperator ){ #- non-strict comparison
			$isequal = $comparisonOperator === '!='?false:true;
			foreach($comparisonDatas as $k=>$v){
				$eq = $v==$exp?true:false;
				if( $isequal===$eq)
					$filtered[] = $this[$k];
			}
		}elseif( 'preg' === $comparisonOperator ){ #- preg match comparison
			foreach($comparisonDatas as $k=>$v){
				if( preg_match($exp,$v) )
					$filtered[] = $this[$k];
			}
		}elseif( in_array($comparisonOperator,array('in','!in','IN','!IN')) ){ # in array/collection comparison
			$strict=$comparisonOperator[strlen($comparisonOperator)-1]==='N'?true:false;
			#- $not   = $comparisonOperator[0]==='!'?'!':'';
			$hasToBeIn = $comparisonOperator[0]==='!'?false:true;
			foreach($comparisonDatas as $k=>$v){
				if( $hasToBeIn === in_array($v,$exp,$strict))
					$filtered[] = $this[$k];
				#- eval('if('.$not.' in_array($v,$exp,$strict)) $filtered[] = $this[$k];');
			}
		}else{ #- user defined comparison
			switch($comparisonOperator){
				case '<=':
					foreach($comparisonDatas as $k=>$v){ if( $v <= $exp) $filtered[] = $this[$k];}
					break;
				case '>=':
					foreach($comparisonDatas as $k=>$v){ if( $v >= $exp) $filtered[] = $this[$k];}
					break;
				case '<':
					foreach($comparisonDatas as $k=>$v){ if( $v < $exp) $filtered[] = $this[$k];}
					break;
				case '>':
					foreach($comparisonDatas as $k=>$v){ if( $v > $exp) $filtered[] = $this[$k];}
					break;
				default:
					foreach($comparisonDatas as $k=>$v){ eval('if( $v '.$comparisonOperator.' $exp) $filtered[] = $this[$k];'); }
			}

		}
		return modelCollection::init($this->collectionType,$filtered);
	}

	/**
	* like array_map but for modelCollection.
	* you can also specify the propertyName you want to apply callBack on.
	* @Note: don't forget that unless you've worked on a cloned collection you will
	*        modify all living instances of models in the collection so use this with care!
	* @param callable $callBack     any valid callable as define in call_user_func
	* @param string   $propertyName optionnal name of property you want to apply callback on
	* @return $this for method chaining
	*/
	public function map($callBack,$propertyName=null){
		$this->loadDatas();
		if( null === $propertyName ){
			foreach($this as $instance)
				call_user_func($callBack,$instance);
		}else{
			foreach($this as $instance)
				$instance->{$propertyName} = call_user_func($callBack,$instance->{$propertyName});
		}
		return $this;
	}

	/**
	* return sum value of given property for all models in collection
	* @param string $propertyName name of the property we want sum value
	* @return mixed
	*/
	public function sum($propertyName){
		if( $this->count() < 1)
			return 0;
		return array_sum($this->getPropertyList($propertyName));
	}
	/**
	* return average value for the given property for models in collection
	* @param string $propertyName name of the property we want average value
	* @param int    $decimal      optionnal number of decimal to keep (use native php round() function)
	* @return float
	*/
	public function avg($propertyName,$decimal=null){
		$ct = $this->count();
		if( $ct < 1)
			return 0;
		$avg = $this->sum($propertyName) / $ct;
		return null===$decimal?$avg : round($avg,(int)$decimal);
	}
	/**
	* return max value of given property for all models in collection
	* @param string $propertyName name of the property we want sum value
	* @return mixed
	*/
	public function max($propertyName){
		if( $this->count() < 1)
			return 0;
		return $this->clonedCollection()->sort($propertyName)->last()->{$propertyName};
	}
	/**
	* return min value of given property for all models in collection
	* @param string $propertyName name of the property we want sum value
	* @return mixed
	*/
	public function min($propertyName){
		if( $this->count() < 1)
			return 0;
		return $this->clonedCollection()->sort($propertyName)->first()->{$propertyName};
	}


	/**
	* will clone all instances of models in collection, this can be used to apply some methods on collection
	* without modifying real instances of models that live in the rest of the programm
	* (example you can apply a modelCollection::map('strtoupper',$propertyName) for rendering purpose,
	*  whitout really impacting living instance.)
	*/
	public function clonedCollection(){
		$this->loadDatas();
		$clone = modelCollection::init($this->collectionType);
		foreach($this as $k=>$v){
			$clone[$k] = clone $v;
		}
		return $clone;
	}

	###--- HTML HELPERS ---###
	/**
	* return a html string containing option elements for each models in the collection.
	* (the value parameter is always the primaryKey field)
	* @param string $labelString   string of labels where %dataKey will be replaced with their corresponding values
	*                              if empty will use the default model::__toString method as label
	* @param mixed  $selected      the model selected or it's PK value
	*                              can also be a list of PK or a modelCollection
	* @param mixed  $removedModels modelCollection or list of models PK to exclude from the results
  * @param string $optGroup      optional property to use as a gouping property (used to create optGroup elements)
	* @return string html
	*/
	public function htmlOptions($labelString,$selected=null,$removedModels=null,$disabledModels=null,$optGroup=null){
		$opts = array();
		$isRelated = false;
		if(null !== $optGroup){
			$isRelated = abstractModel::_getModelStaticProp($this->collectionType,'hasOne');
			$isRelated = isset($isRelated[$optGroup]);
		}
		$this->loadDatas($isRelated ? $optGroup : null);

		if( $selected instanceof $this->collectionType || $selected instanceof modelCollection)
			$selected = $selected->PK;
		#- $removedModels must be an array of instance keys
		if(null === $removedModels)
			$removedModels = array();
		if( $removedModels instanceof modelCollection )
			$removedModels = $removedModels->PK;
		$removedModels = array_flip($removedModels);
		#- same for disabled models
		if(null === $disabledModels)
			$disabledModels = array();
		if( $disabledModels instanceof modelCollection )
			$disabledModels = $disabledModels->PK;
		$disabledModels = array_flip($disabledModels);
		$previousOptGroup = false;
		foreach($this as $item){
			if( isset($removedModels[$item->PK]) )
				continue;
			#- prepare label
			$label  = empty($labelString)?"$item":$item->_toString($labelString);
			if(is_array($selected))
				$_selected = in_array($item->PK,$selected);
			else
				$_selected = ($item->PK==$selected)?true:false;
			if(null !== $optGroup && !empty($item->{$optGroup}) && $previousOptGroup !== ($isRelated ? $item->{$optGroup}->PK : $item->{$optGroup})) {
				if(false !== $previousOptGroup)
					$opts[] = "</optgroup>";
				$previousOptGroup = $isRelated ? $item->{$optGroup}->PK : $item->{$optGroup};
				$optlabel = $isRelated ? $item->{$optGroup}->__toString() : $optGroup;
				$opts[] = "<optgroup label=\"$optlabel\">";
			}
			$opts[] = "\t<option value=\"$item->PK\"".($_selected?' selected="selected"':'')
				.(isset($disabledModels[$item->PK])?' disabled="disabled"':'').">$label</option>";
		}
		if(false !== $previousOptGroup){ // close last optgroup (if opened)
			$opts[] = "</optgroup>\n";
		}
		return implode("\n\t",$opts);
	}

	/**
	* @param str $formatStr format string as used by abstractModel::__toString() methods.
	*                       in addition to other format options you can use %model that will be replaced
	*                       with the default model::$__toString property.
	*                       if left null then the default model::$__toString property will be used for rendering.
	* @param str $separator string separator between models
	* @see abstractModel::__toString() for more infos
	*/
	public function _toString($formatStr=null,$separator=''){
		$this->loadDatas();
		$modelFormatStr = abstractModel::_getModelStaticProp($this->collectionType,'__toString');
		$formatStr = null===$formatStr?$modelFormatStr:preg_replace('!%model(?=\W|$)!',$modelFormatStr,$formatStr);
		$str = array();$i=0;
		foreach($this as $m){
			${'model'.++$i}=$m;
			$str[]= preg_replace('/(?<!%)%(?!%)([A-Za-z_][A-Za-z0-9_]*)/','$model'.$i.'->\\1',$formatStr);
		}
		$str=implode($separator,$str);
		$str = preg_replace(array('/(?<!%)%{(.*?)}%(?!%)/s','!%%!'),array("\n__TOSTRING\n.(\\1).<<<__TOSTRING\n",'%'),$str);
		return eval('return<<<__TOSTRING'."\n$str\n__TOSTRING;\n");
	}
	//-- backward compatibility with older versions
	public function __toString(){
		if(! func_num_args() ){
			return call_user_func(array($this,'_toString'));
		}else{
			$args = func_get_args();
			return call_user_func_array(array($this,'_toString'),$args);
		}
	}
}

/**
* provides all basics methods required by models.
* abstractModel is also the place where all instances of models are kept in memory to allow each models to be loaded only once.
* @class abstractModel
*/
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
	#- set this on true to bypass filters and user defined setters when required (modelCollection use this at loadDatas() time)
	public $bypassFilters = false;
	/** specificly added for simpleMVC.
	* this is meant to be the name of langManager dictionary where filtersMsgs will be looked for.
	* leave to null if you don't want to use this stuff. you can also defined a not static property
	* filtersDictionary inside models final class if you want to override this default setting.
	*/
	static public $dfltFiltersDictionary = null;

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
	static protected $dbConnectionDescriptor = null;

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
	* hold modelAddons names the model can manage
	*/
	static protected $modelAddons = array();
	/**
	* hold instances of addons attached to the model
	*/
	protected $_modelAddons = array();

	/**
	* will keep trace of each model instances to permit uniques instances
	* of any models.
	*/
	static protected $instances = array();
	/**
	* just a place to keep some various internal datas to avoid of preparing them more than once
	* (for exemples some regexps inside magic methods)
	*/
	static private $_internals = array('_pagedNav'=>array());
	static public $_pagedNavDefault = array(
		'firstDisabled' => false,
		'prevDisabled'  => false,
		'nextDisabled'  => false,
		'lastDisabled'  => false,
		'first' => '<a href="%lnk" class="pagelnk"><big>&laquo;</big></a>',
		'prev'  => '<a href="%lnk" class="pagelnk"><big>&lsaquo;</big></a>',
		'next'  => '<a href="%lnk" class="pagelnk"><big>&rsaquo;</big></a>',
		'last'  => '<a href="%lnk" class="pagelnk"><big>&raquo;</big></a>',
		'pages' => '<a href="%lnk" class="pagelnk">%page</a>',
		'curpage'  => "<b><a href=\"%lnk\" class=\"pagelnk\">%page</a></b>",
		'linkStr'  => '/%modelName/list/page/%page',
		'linkSep'  => ' ',
		'formatStr'=> ' %first %prev %5links %next %last'
	);

	/**
	* use dbProfiler to encapsulate db instances (used for debug and profiling purpose)
	*/
	static public $useDbProfiler = false;

	/** formatString to display model as string */
	static public $__toString = '';

	/**
	* if true then the model can't have an empty primaryKey value (empty as in php empty() function)
	* so passing an empty PrimaryKey at getInstance time will result to be equal to a getNew call
	*/
	static protected $_avoidEmptyPK = false;
	/**
	* only for debug purpose
	* @todo delete this debug method
	*/
	static public function showInstances($compact=true){
		if(! $compact)
			return show(self::$instances);
		$res = array();
		foreach(self::$instances as $model=>$instances){
			if( $compact === true ){
				$res[$model] = array_keys($instances);
			}else{
				$res[$model] = modelCollection::init($model,$instances);
				$res[$model] = $res[$model]->{$compact};
			}
		}
		show($res,'color:#055;');
	}
	/**
	* create an instance of model.
	* @param str  $PK       if given retrieve the datas for the given primary key object.
	*                       else return a new empty model object.
	* @param bool $isDummyInstance this is only there for internal purpose (such as call to getModelDbAdapter method)
	*/
	protected function __construct($PK=null,$isDummyInstance=false){
		#- first set internalDatas
		self::_initInternals(get_class($this));
		#- link dbAdapter instance
		$this->dbAdapter = db::getInstance(self::_getModelStaticProp($this,'dbConnectionDescriptor'));
		if( self::$useDbProfiler )
			$this->dbAdapter = new dbProfiler($this->dbAdapter);
		#- dummy instances do nothing more and return
		if( $isDummyInstance ){
			return;
		}
		#- init addons for real instances
		$this->_initModelAddons($PK);
	}
	/**
	* Set some internalDatas for easier further access You should never need this
	* The only purpose of this method if for internal use so don't rely on this at
	* it may change/disappear in further version without any warning
	* @internal
	* @param string $className the modelName
	* @param bool $return return _internals[className] if given
	* @return mixed array the _internals datas setted if $return is true else true
	*/
	static public function _initInternals($className,$return=false){
		if(! empty(self::$_internals[$className]) )
			return $return?self::$_internals[$className]:true;
		self::$_internals[$className] = array(true); //-- avoid further call while working
		$oneKeys = $manyKeys = $datasKeys = array();
		#- override/extends hasOne and hasMany property if required
		$classVars = get_class_vars($className);
		if(isset($classVars['_hasOne'])){
			$_hasOne = self::_getModelStaticProp($className,'_hasOne');
			if(! empty($_hasOne)){
				$hasOne = self::_getModelStaticProp($className,'hasOne');
				foreach($_hasOne as $k=>$v){
					if( empty($v) && isset($hasOne[$k]))
						unset($hasOne[$k]);
					else
						$hasOne[$k] = $v;
				}
				eval("$className::\$hasOne = \$hasOne;");
			}
		}
		if(isset($classVars['_hasMany'])){
			$_hasMany = self::_getModelStaticProp($className,'_hasMany');
			if(! empty($_hasMany)){
				$hasMany = self::_getModelStaticProp($className,'hasMany');
				foreach($_hasMany as $k=>$v){
					if( empty($v) && isset($hasMany[$k]))
						unset($hasMany[$k]);
					else
						$hasMany[$k] = $v;
				}
				eval("$className::\$hasMany = \$hasMany;");
			}
		}
		#- prepare related keys exp
		foreach(array_keys(self::_getModelStaticProp($className,'hasOne')) as $k)
			$oneKeys[] = '['.$k[0].strtoupper($k[0]).']'.substr($k,1);
		self::$_internals[$className]['hasOneKeyExp'] = implode('|',$oneKeys);
		foreach(array_keys(self::_getModelStaticProp($className,'hasMany')) as $k)
			$manyKeys[] = '['.$k[0].strtoupper($k[0]).']'.substr($k,1);
		self::$_internals[$className]['hasManyKeyExp'] = implode('|',$manyKeys);
		self::$_internals[$className]['has*KeyExp'] = self::$_internals[$className]['hasOneKeyExp']
			.((count($oneKeys)&&count($manyKeys))?'|':'')
			.self::$_internals[$className]['hasManyKeyExp'];
		#- prepare datas keys exp
		$datasDefs = self::_getModelStaticProp($className,'datasDefs');
		foreach(array_keys($datasDefs) as $k)
			$datasKeys[] = '['.$k[0].strtoupper($k[0]).']'.substr($k,1);
		$datasKeys[] = 'PK';
		self::$_internals[$className]['datasKeyExp'] = implode('|',$datasKeys);

		return $return?self::$_internals[$className]:true;
	}

	private function _initModelAddons($PK){
		$modelAddons = self::_getModelStaticProp($this,'modelAddons');
		if( empty($modelAddons) )
			return false;
		foreach($modelAddons as $addon){
			$addonClass = $addon.'ModelAddon';
			$_addon = new $addonClass($this,$PK);
			$this->_modelAddons[$addon] = $_addon;
		}
		return true;
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
		#- then update related models with correct values would be fine but how to achieve this (pattern observer?) ?
	}

	/**
	* check if there is any living (already loaded) instance of the given model for matching PK
	* @param string $modelName   model name
	* @param mixed  $PK          value of the primary key
	* @param bool   $returnModel set this to true to return living model instead of true on success
	* @return bool or abstractModel/null if $returnModel is true
	*/
	static public function isLivingModelInstance($modelName,$PK,$returnModel=false){
		#- ~ show($PK,(isset(self::$instances[strtolower($modelName)][$PK])?true:false),'color:green');
		if(! isset(self::$instances[strtolower($modelName)][$PK])){
			#- ~ self::showInstances('datas');
			return $returnModel?null:false;
		}
		return $returnModel?self::$instances[strtolower($modelName)][$PK]:true;
	}


	/**
	* This is not intended to be used by end users!!!
	* The purpose of this method is to allow accessing an instance of an object with no datas or modelAddons connected to it.
	* It's typically used for some modelAddons developpement or as internal method ( used by getModelDbAdapter for example )
	* return abstractModel (no need to call detach as it's not "attached" at all )
	*/
	static public function getModelDummyInstance($modelName){
		return new $modelName(null,true);
	}
	/**
	* return a collection of all instanciated instances of a given model
	* @param mixed $modelName string modelName or abstractModel instance of the given model.
	* @return modelCollection
	*/
	static public function getModelLivingInstances($modelName){
		if( $modelName instanceof abstractModel)
			$modelName=$modelName->modelName;
		return modelCollection::init($modelName,empty(self::$instances[strtolower($modelName)])?null:self::$instances[strtolower($modelName)]);
	}

	/**
	* return unique abstractModel instance by primary key or a new empty one.
	* @param string $modelName  model name
	* @param mixed  $PK         value of the primary key
	* @return abstractModel or null on error
	*/
	static public function getModelInstance($modelName,$PK=null){
		#- make some check on PK (type/empty)
		$primaryKey = self::_getModelStaticProp($modelName,'primaryKey');
		if( empty($PK) && self::_getModelStaticProp($modelName,'_avoidEmptyPK') )
			$PK = null;
		else
			$PK = self::setModelDatasType($modelName,$primaryKey,$PK);
		# check for living instance
		if(null!==$PK){
			$instance = self::isLivingModelInstance($modelName,$PK,true);
			if($instance instanceof abstractModel)
				return $instance;
		}
		#- haven't found any living instance so get one
		$instance = new $modelName($PK);

		#- newly created model we just set a temporary primaryKey and return it
		if( null === $PK ){
			$instance->datas[$primaryKey] = uniqid('abstractModelTmpId',true);
			self::_setInstanceKey($instance);
			return $instance;
		}
		#- if primaryKey is already set in datas this normaly mean that modelAddons have do the setDatas job so we just end here
		if( null !== $PK && $PK===$instance->PK ){
			self::_setInstanceKey($instance);
			return $instance;
		}
		#- finally if we're there get datas from db and set them
		$instance->datas = $instance->dbAdapter->select_single_to_array(
			self::_getModelStaticProp($instance,'tableName'),
			'*',
			array('WHERE '.$instance->dbAdapter->protect_field_names($primaryKey).' = ?',$PK)
		);
		if( false === $instance->datas) #- error no datas in database
			return null;
		$instance->setModelDatasTypes();
		self::_setInstanceKey($instance);
		return $instance;
	}

	/**
	* return an instance of $modelName and set instance datas.
	* if $datas contains a field with primaryKey it will check first for a living instance (already loaded  not existing in database) of the given datas
	* @param string $modelName
	* @param array  $datas
	* @param bool   $dontOverideIfExists this only make sense if you have the primaryKey field set in datas
	*                                    in this case if true and a living instance (not checked in database but in loaded instances) is found then it will simply return the instance as found
	*                                    else it will set instance datas to the one given. (can be of help to set multiple keys at once)
	* @param bool   $bypassFilters       if true then will bypass datas filtering and users setters
	* @param bool   $leaveNeedSaveState  by default setting datas will set $this->needSave to 1, setting this parameter to true
	*                                    will leave $this->needSave to its previous state (generally used by modelCollection::loadDatas()).
	* @return abstractModel
	*/
	static public function getModelInstanceFromDatas($modelName,$datas,$dontOverideIfExists=false,$bypassFilters=false,$leaveNeedSaveState=false){
		#- make some check on PK (type/empty)
		$primaryKey = self::_getModelStaticProp($modelName,'primaryKey');
		#-  have we a primaryKey in datas or not?
		if(!  isset($datas[$primaryKey])){
			$PK = null;
		}else{
			$PK = $datas[$primaryKey];
			if( empty($PK) && self::_getModelStaticProp($modelName,'_avoidEmptyPK') )
				$PK = null;
			else
				$PK = self::setModelDatasType($modelName,$primaryKey,$PK);
			unset($datas[$primaryKey]);
		}
		$instance = null;

		#- check for living instance
		if(null!==$PK){
			$instance = self::isLivingModelInstance($modelName,$PK,true);
			if( $instance instanceof abstractModel){
				if(! $dontOverideIfExists )
					$instance->_setDatas($datas,$bypassFilters,null,$leaveNeedSaveState);
				return $instance;
			}
		}
		#- here we haven't found any living instance or haven't any PK so just get a new instance
		$instance = new $modelName($PK);

		if( null===$PK )#- no primaryKey given get a temporary Key
			$PK = uniqid('abstractModelTmpId',true);
		$instance->datas[$primaryKey] = $PK;
		self::_setInstanceKey($instance);
		#- set Datas
		return $instance->_setDatas($datas,$bypassFilters,null,$leaveNeedSaveState);
	}

	/**
	* return array of abstractModel instances by primaryKeys
	* @param string $modelName
	* @param array  $PKs primary keys of desired models
	* @return modelCollection indexed by their primaryKeys
	*/
	static public function getMultipleModelInstances($modelName,array $PKs){
		return modelCollection::init($modelName,$PKs);
	}

	/**
	* return multiple instances of modelName that match simple given filter
	* @param string $modelName
	* @param array  $filter    same as conds in class-db methods
	* @return modelCollection indexed by their primaryKeys
	*/
	static public function getFilteredModelInstances($modelName,$filter=null){
		$tableName  = self::_getModelStaticProp($modelName,'tableName');
		$primaryKey = self::_getModelStaticProp($modelName,'primaryKey');
		$db = self::getModelDbAdapter($modelName);
		$PKs = $db->select_col($tableName,$primaryKey,$filter);
		$db->freeResults();
		return self::getMultipleModelInstances($modelName,empty($PKs)?array():$PKs);
	}
	/**
	* return single instance of modelName that match given filter
	* @param string $modelName
	* @param array  $filter    same as conds in class-db methods
	* @return single abstractModel instance or null if not found
	*/
	static public function getFilteredModelInstance($modelName,$filter=null){
		$tableName  = self::_getModelStaticProp($modelName,'tableName');
		$primaryKey = self::_getModelStaticProp($modelName,'primaryKey');
		$db = self::getModelDbAdapter($modelName);
		$PK = $db->select_value($tableName,$primaryKey,$filter);
		if( false===$PK)
			return null;
		return self::getModelInstance($modelName,$PK);
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
			throw new InvalidArgumentException(__class__.'::'.__function__.'() invalid parameter filterType('.$filterType.') must be one of '.implode('|',array_keys($filterTypes)).'.');
		$field = self::_cleanKey($modelName,'datasDefs',$field);
		if($field === false)
			throw new OutOfBoundsException(__class__.'::'.__function__.'() invalid parameter field('.$field.') must be a valid datas fieldName.');
		if(! is_array($args) )
			$args = array($args);
		$field = abstractModel::getModelDbAdapter($modelName)->protect_field_names($field);
		if($filterType==='Between')
			$filter = "WHERE $field  BETWEEN ? AND ?";
		else
			$filter = "WHERE $field ".$filterTypes[$filterType];
		if(substr($filterType,-2)==='In')
			$args = array($args);
		array_unshift($args,$filter);

		return abstractModel::getFilteredModelInstances($modelName,$args);
	}

	/**
	* return all instances of modelName in databases and load them all at once
	* precaution must be taken in using this method against large table as it will load ALL datas in the table on EACH call
	* so this may be a performance issue if misused !!!
	* @param string $modelName
	* @param string $withRelated string of related stuffs to load at the same time. multiple values are separated by |
	* @param string $orderedBY   an SQL ORDER BY clause, no order by default
	* @return modelCollection indexed by their primaryKeys
	*/
	static public function getAllModelInstances($modelName,$withRelated=null,$orderedBY=null){
		$tableName  = self::_getModelStaticProp($modelName,'tableName');
		$db = self::getModelDbAdapter($modelName);
		$rows = $db->select_rows($tableName,'*',$orderedBY);
		$db->freeResults();
		$collection = modelCollection::init($modelName);
		if( $rows === false ){
			return $collection;
		}
		foreach($rows as $row)
			$collection[] = self::getModelInstanceFromDatas($modelName,$row,true,true,true);
		$rows = null;
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
		$rows = $db->select_slice($tableName,'*',$filter,$pageId,$pageSize,self::_setModelPagedNav($modelName));
		$db->freeResults();
		$collection = modelCollection::init($modelName);
		if( $rows === false )
			return array($collection,'',0);
		list($rows,$nav,$total) = $rows;
		if(! empty($rows)){
			foreach($rows as $row)
				$collection[] = self::getModelInstanceFromDatas($modelName,$row,true,true,true);
			$rows  = null;
			if( null !== $withRelated )
				$collection->loadDatas($withRelated);
		}
		return array($collection,$nav,$total);
	}
	/**
	* set page navigation string for the given model.
	* in fact just a wrapper to model::dbAdapter->set_slice_attrs
	* @see db::set_slice_attrs for more info
	* @return array() sliceAttrs (full attrs)
	*/
	static public function _setModelPagedNav($modelName,array $sliceAttrs=null){
		if( null !== $sliceAttrs){
			$sliceAttrs = array_merge(self::$_pagedNavDefault,array_intersect_key($sliceAttrs,self::$_pagedNavDefault));
		}else{
			if( ! empty(self::$_internals['_pagedNav'][$modelName]) ){
				return self::$_internals['_pagedNav'][$modelName];
			}else{
				$sliceAttrs = self::$_pagedNavDefault;
			}
		}
		foreach($sliceAttrs as &$v)
			$v = str_replace('%modelName',$modelName,$v);
		return self::$_internals['_pagedNav'][$modelName] = $sliceAttrs;
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
		$PKExists   = $db->select_value($tableName,$primaryKey,array('WHERE '.$db->protect_field_names($primaryKey).'=?',$PK));
		return $valids[$modelName][$PK]=$PKExists!==false?true:false;
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
				$tmpCollection = self::getModelLivingInstances($relDef['modelName'])->filterBy($relDef['foreignField'],$localFieldVal);
				if($tmpCollection->count()){
					$tmpModel = $tmpCollection->current();
				}
				if(!isset($tmpModel)){
					$tmpModel = self::getFilteredModelInstance(
						$relDef['modelName'],
						array('WHERE '.abstractModel::getModelDbAdapter($relDef['modelName'])->protect_field_names($relDef['foreignField']).'=? LIMIT 0,1',$localFieldVal)
					);
				}
			}else{ # foreignKey is primaryKey
				$tmpModel = self::getModelInstance($relDef['modelName'],$localFieldVal);
			}
			if(! $tmpModel instanceof abstractModel) # no related object was found in database create a new one
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
				return $this->_manyModels[$relName] = modelCollection::init($relDef['modelName']);

			$localFieldVal = $this->datas[$relDef['localField']];
			if( empty($relDef['linkTable']) ){
				$dbAdapter = abstractModel::getModelDbAdapter($relDef['modelName']);
				return $this->_manyModels[$relName] = abstractModel::getFilteredModelInstances(
					$relDef['modelName'],
					array(
						'WHERE '.$dbAdapter->protect_field_names($relDef['foreignField']).' =?'.(empty($relDef['orderBy'])?'':' ORDER BY '.$relDef['orderBy']),
						$localFieldVal
					)
				);
			}else{
				if( empty($relDef['orderBy']) ){
					$PKs = $this->dbAdapter->select_col(
						$relDef['linkTable'],
						$this->dbAdapter->protect_field_names($relDef['linkForeignField']),
						array('WHERE '.$this->dbAdapter->protect_field_names($relDef['linkLocalField']).'=?',$localFieldVal)
					);
				}else{
					$relTable      = self::_getModelStaticProp($relDef['modelName'],'tableName');
					$relPrimaryKey = self::_getModelStaticProp($relDef['modelName'],'primaryKey');
					$PKs = $this->dbAdapter->select_col(
						"$relDef[linkTable] LEFT JOIN $relTable ON $relDef[linkTable].$relDef[linkForeignField] = $relTable.$relPrimaryKey",
						$this->dbAdapter->protect_field_names($relDef['linkForeignField']),
						array(
							'WHERE '.$this->dbAdapter->protect_field_names($relDef['linkTable']).'.'
							.$this->dbAdapter->protect_field_names($relDef['linkLocalField'])
							.'=? ORDER BY '.$relDef['orderBy'],
							$localFieldVal
						)
					);
				}
				$this->dbAdapter->freeResults();
				return $this->_manyModels[$relName] = abstractModel::getMultipleModelInstances($relDef['modelName'],empty($PKs)?array():$PKs);
			}
		}

		throw new OutOfBoundsException(get_class($this)."::getRelated($relName) unknown relation");
	}

	public function isRelatedSet($k){
		return !(empty($this->_oneModels[$k]) && empty($this->_manyModels[$k]));
	}

	/**
	* check if a method is supported by current instance of the object (also check if modelAddon overload the method)
	* @param string $methodName
	* @param bool   $returnCallable if set to true return callable instead of bool
	* @param bool   $avoidStatic if true will not consider static method as true
	* @return bool or callable depending on $returnCallable value
	* @see call_user_func for callable definition
	*/
	public function _methodExists($methodName,$returnCallable=false,$avoidStatic=false){
		#- first check for modelAddons overloaded methods
		foreach($this->_modelAddons as $addon){
			if( $addon->isModelMethodOverloaded($methodName) )
				return $returnCallable?array($addon,$methodName):true;
		}
		#- then check inside the instance (and avoid returning static method as existant)
		if( method_exists($this,$methodName) ){
			if( $avoidStatic ){
				$tmp = new ReflectionMethod($this,$methodName);
				if( $tmp->isStatic() )
					return false;
			}
			return $returnCallable?array($this,$methodName):true;
		}
		return false;
	}
	/**
	* return an associative array with each properties
	* @param mixed  $propertiesNames list of propery to get from can be an array
	*                                or a string with properties separated by any of the following chars |,;
	* @param string $concatSeparator if $concatSeparator is passed then will implode result using given string as separator
	* @return array
	*/
	public function _getProperties($propertiesNames,$concatSeparator=null){
		$properties = is_array($propertiesNames)?$propertiesNames:preg_split('![,|;]!',$propertiesNames);
		foreach($properties as $p)
			$res[$p] = $this->{$p};
		return (null!==$concatSeparator)?implode($concatSeparator,$res):$res;
	}
	###--- MAGIC METHODS ---###
	public function __get($k){
		#- first check primary key
		if( $k === 'PK' )
			return $this->datas[self::_getModelStaticProp($this,'primaryKey')];

		#- if  user defined getter exists we just call it and return
		if( $this->_methodExists("get$k",false,true) )
			return $this->{"get$k"}();

		$hasOne = self::_getModelStaticProp($this,'hasOne');
		$hasMany = self::_getModelStaticProp($this,'hasMany');
		#- then check related objects first
		if( isset($hasOne[$k]) || isset($hasMany[$k]) )
			return $this->getRelated($k);
		#- then check for datas values
		if( isset($this->datasDefs[$k]) )
				return $this->datas[$k];
		#- then protected properties (make them kind of read only values)
		if( isset($this->$k) )
			return $this->$k;
		#- finally common static properties
		if( in_array($k,array('modelName','tableName','primaryKey','datasDefs','hasOne','hasMany','filters','dfltFiltersDictionary','modelAddons','__toString','_avoidEmptyPK'),1) )
			return self::_getModelStaticProp($this,$k);
		#- nothing left throw an exception
		throw new OutOfBoundsException(get_class($this)."::$k unknown property.");
	}

	/**
	* setter for datas and hasOne relations
	*/
	public function __set($k,$v){
		if($k === 'PK' || $k === self::_getModelStaticProp($this,'primaryKey') )
			throw new RuntimeException(get_class($this)." primaryKey can not be set by user.");

		#- apply filters
		if(! $this->bypassFilters){
			$v = $this->filterData($k,$v);
			if($v === false){
				return false;
			}
			#- call user defined setter first
			if( $this->_methodExists("set$k",false,true) ){
				$this->{"set$k"}($v);
				return $v;
			}
		}
		$hasOne = self::_getModelStaticProp($this,'hasOne');
		if(isset($hasOne[$k])){
			$this->needSave = 1; #- for now we change the needSave state will see later to check for unchanged values
			$relModelName   = $hasOne[$k]['modelName'];
			$thisPrimaryKey = self::_getModelStaticProp($this,'primaryKey');
			$localField     = empty($hasOne[$k]['localField'])? $thisPrimaryKey : $hasOne[$k]['localField'];
			if( $this->bypassFilters ){ //-- bypass filter so make a quicker version
				if( is_object($v) ){
					if(! $v instanceof $relModelName)
						throw new UnexpectedValueException(get_class($this)." error while trying to set an invalid $k value(".get_class($v).").");
					$this->_oneModels[$k] = $v;
					if(isset($this->datasDefs[$localField]) && $localField !== $thisPrimaryKey){
						$this->datas[$localField] = $v->datas[empty($hasOne[$k]['foreignField'])?$v->primaryKey:$hasOne[$k]['foreignField']];
					}
					return $v;
				}else{
					$this->datas[$localField] = self::setModelDatasType($this,$localField,$v);
					if( isset($this->_oneModels[$k])){
						unset($this->_oneModels[$k]); //-- sort of way to empty the cached related object, next call to get the related will dynamicly reload the related if required
					}
					return $v;
				}
			}
			$foreignPrimaryKey = self::_getModelStaticProp($relModelName,'primaryKey');
			$foreignField   = empty($hasOne[$k]['foreignField'])? $foreignPrimaryKey : $hasOne[$k]['foreignField'];
			if( is_object($v) ){
				if(! $v instanceof $relModelName)
					throw new UnexpectedValueException(get_class($this)." error while trying to set an invalid $k value(".get_class($v).").");
				$this->_oneModels[$k] = $v;
				if(isset($this->datasDefs[$localField]) && $localField !== $thisPrimaryKey)
					$this->datas[$localField] = $v->datas[$foreignField];
				return $v;
			}
			#- here we deal with a non object value
			#- @todo in fact will be better to only check that the value is an existing key for model and not create an instance
			switch( $hasOne[$k]['relType']){
				case 'dependOn': #- check for data integrity REQUIRED so if we must check in database load the model at this time
					$validValue = false;
					if( $this->bypassFilters ){
						$validValue = true;
					}else if( $foreignField===$foreignPrimaryKey ){
						if( self::existsModelPK($relModelName,$v) )
							$validValue = true;
					}else if( $foreignPrimaryKey!==$foreignField ){
						if(!  self::getModelLivingInstances($relModelName)->filterBy($foreignField,$v)->isEmpty() ){ #- check living instance
							$validValue = true;
						}else if(! abstractModel::getFilteredModelInstancesByField($relModelName,$foreignField,'Equal',$v)->isEmpty() ){ #- check exists in database
							$validValue = true;
						}
					}
					if( $validValue ){
						$this->datas[$localField] = self::setModelDatasType($this,$localField,$v);
					}else{
						throw new UnexpectedValueException(get_class($this)." error while trying to set an invalid $k value($v).");
					}
					break;
				case 'requiredBy': #- as we don't rely on this relation there's no such big deal to be confident in the user to give correct value,
				case 'ignored':    #- at least if datas are really invalid it will trigger a database error at save time
					if($localField===$thisPrimaryKey)
						throw new UnexpectedValueException(get_class($this)." error while trying to set an invalid $k value($v).");
					$this->datas[$localField] = self::setModelDatasType($this,$localField,$v);
					break;
			}
			if( isset($this->_oneModels[$k])){
				unset($this->_oneModels[$k]); //-- sort of way to empty the cached related object, next call to get the related will dynamicly reload the related if required
			}
			return $v;
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

		if(isset($this->datasDefs[$k])){
			$v = self::setModelDatasType($this,$k,$v);
			if( $this->datas[$k] === $v ){
				return $v;
			}else{
				$this->needSave = 1;
				return $this->datas[$k] = $v;
			}
		}

		throw new OutOfBoundsException(get_class($this)." trying to set unknown property $k.");

	}

	public function __isset($k){
		return isset($this->datasDefs[$k]);
	}

	/**
	* manage dynamic methods calls.
	* here's a list of what type of calls are managed with a sample prototype and in the order they're looked for:
	* - modelAddons methods (see corresponding modelAddons for methods definition)
	* - append[_hasManyName|HasManyName](abstractModel $modelInstance=null) and return this
	*   (also have appendNew[_hasManyName|HasManyName] equal to append[_hasManyName|HasManyName](null))
	* - set[_hasManyName|HasManyName]Collection(array|modelCollection $collection) and return this
	* - get[_has*Name|Has*Name]() is shorthand for getRelated($has*Name) see getRelated() methods for more infos
	* - getFiltered[_hasManyName|HasManyName]($propertyName,$exp,$comparisonOperator=null) sort of $this->getRelated()->getFilteredBy() trying to be optimized
	* - get[_dataKey|DataKey]() return the corresponding value in this->datas
	* - set[_dataKey|DataKey]($value,$bypassFilters=false,$leaveNeedSaveState=false) shortHand for _setData($dataKey,...) return this
	* if none of above methods are found then will throw an exception
	*/
  public function __call($m,$a){
		$className = get_class($this);

		#- first check for modelAddons methods
		foreach($this->_modelAddons as $addon){
			if( $addon->isModelMethodOverloaded($m) ){
				return call_user_func_array(array($addon,$m),$a);
			}
		}

		#- manage add methods for hasMany related
		if(preg_match('!^append(_new|New)?_?('.self::$_internals[$className]['hasManyKeyExp'].')$!',$m,$match) ){
			$relName = self::_cleanKey($this,'hasMany',$match[2]);
			if($relName===false)
				throw new BadMethodCallException("$className trying to call unknown method $m with no matching hasMany[$match[1]] definition.");
			$modelCollection = $this->getRelated($relName);
			$model = array_shift($a);
			$hasMany = self::_getModelStaticProp($this,'hasMany');
			if(null===$model || $match[1])
				$model = self::getModelInstance($hasMany[$relName]['modelName']);
			$modelCollection[] = $model;
			if(isset($hasMany[$relName]['localField']))
				$this->needSave = 1;
			if( isset($hasMany[$relName]['foreignField']) ) # set reverse relation
				$model->{$hasMany[$relName]['foreignField']} = isset($hasMany[$relName]['localField'])?$this->{$hasMany[$relName]['localField']}:$this->PK;
			$this->needSave = 1; //- mark as need save to force update of related collection when calling save on this element
			return $match[1]?$model:$this;#- @todo make reflection on what should be return for now i think that allowing method chaining can be nice
		}

		#- manage setter methods for hasMany related
		if( preg_match('!set_?('.self::$_internals[$className]['hasManyKeyExp'].')_?[cC]ollection$!',$m,$match) ){
			$relName = self::_cleanKey($this,'hasMany',$match[1]);
			$relDef = self::_getModelStaticProp($this->modelName,'hasMany');
			if($relName === false)
				throw new BadMethodCallException("$className::$m unknown hasMany relation.");
			if( count($a) !== 1 )
				throw new InvalidArgumentException("$className::$m invalid count of parameters");
			$collection = $a[0];
			if(is_array($collection))
				$collection = modelCollection::init($relDef[$relName]['modelName'],$collection);
			elseif(! $collection instanceof modelCollection )
				throw new InvalidArgumentException("$className::$m invalid parameter $collection given, modelCollection expected.");
			$this->_manyModels[$relName] = $collection;
			#- set la relation dans l'autre sens
			if(! empty($relDef[$relName]['foreignField']) )
				$this->_manyModels[$relName]->{$relDef[$relName]['foreignField']} = empty($relDef[$relName]['localField'])?$this->PK:$this->{$relDef[$relName]['localField']};
			$this->needSave = 1; //- mark as need save to force update of related collection when calling save on this element
			return $this; #- @todo make reflection on what should be return for now i thing that allowing method chaining can be nice
		}

		#- manage getter methods for related
		if( preg_match('!^get(Filtered)?_?('.self::$_internals[$className]['has*KeyExp'].')$!',$m,$match) ){
			$relName = self::_cleanKey($this,'hasMany',$match[2]);
			if( empty($match[1]) ){ //- no filter use getRelated
				return $this->getRelated($relName);
			}
			if( count($a) < 2 ){
				throw new InvalidArgumentException("$className::$m require at least 2 parameters");
			}
			$relDef = self::_getModelStaticProp($this->modelName,'hasMany');
			$a[2] = isset($a[2])?$a[2]:null;
			if( empty($relDef[$relName]) ){
				throw new BadMethodCallException("$className trying to call unknown method $m with no matching hasMany[$relName] definition.");
			}else if( !empty($this->_manyModels[$relName]) ){ //-- rel already loaded just apply filter on them
				return $this->getRelated($relName)->filterBy($a[0],$a[1],$a[2]);
			}else{ // rel isn't loaded we try to only retrieved what is asked from database
				#- first check that we're working on a dataField else just load the heavy way
				$relDatasDef = self::_getModelStaticProp($relName,'datasDefs');
				if(! isset($relDatasDef[$a[0]]) ){
					return $this->getRelated($relName)->filterBy($a[0],$a[1],$a[2]);
				}
				$condAddition = 'AND '.$this->dbAdapter->protect_field_names($a[0]).' ';
				#-- prepare filter statement
				switch($a[2]){
					case '=':
					case null:
						$condAddition .= '= 2?';
						break;
					case '==':
					case '===':
					case '!=':
					case '!==':
						$condAddition .= preg_replace('!=+!','=',$a[2]).' 2?';
						break;
					case '=':
					case '>':
					case '<':
					case '>=':
					case '<=':
						$condAddition .= $a[2].' 2?';
						break;
					case 'in':
					case 'IN':
						$condAddition .= 'IN (2?)';
						if( $a[1] instanceof modelCollection)
							$a[1] = $a[1]->keys();
						break; //do nothing on those
					case '!in':
					case '!IN':
						$condAddition .= 'NOT IN (2?)';
						if( $a[1] instanceof modelCollection)
							$a[1] = $a[1]->keys();
						break;
					default: //-- we don't support SQL translation for now wo so we default to load collection and apply filterBy on it
						return $this->getRelated($relName)->filterBy($a[0],$a[1],$a[2]);
						break;
				}
				#- @TODO this is a modified version of the getRelated on hasMany method should be merged in a unique private function for easyer maitenance
				$relDef = $relDef[$relName];
				#- check that this is not a relation based on an unsaved primaryKey
				$lcPKField = self::_getModelStaticProp($this,'primaryKey');
				if( empty($relDef['localField']) )
					$relDef['localField'] = $lcPKField;
				# if $this is a newly unsaved object it can't already have any existing related object relying on it's primaryKey so return an empty collection
				if( $relDef['localField'] === $lcPKField && $this->isTemporary() ){
					return modelCollection::init($relDef['modelName']);
				}
				$localFieldVal = $this->datas[$relDef['localField']];
				if( empty($relDef['linkTable']) ){
					$dbAdapter = abstractModel::getModelDbAdapter($relDef['modelName']);
					return abstractModel::getFilteredModelInstances(
						$relDef['modelName'],
						array(
							'WHERE '.$dbAdapter->protect_field_names($relDef['foreignField']).' =? '.$condAddition.(empty($relDef['orderBy'])?'':' ORDER BY '.$relDef['orderBy']),
							$localFieldVal,
							$a[1]
						)
					);
				}else{
					$relTable      = self::_getModelStaticProp($relDef['modelName'],'tableName');
					$relPrimaryKey = self::_getModelStaticProp($relDef['modelName'],'primaryKey');
					$PKs = $this->dbAdapter->select_col(
						"$relDef[linkTable] LEFT JOIN $relTable ON $relDef[linkTable].$relDef[linkForeignField] = $relTable.$relPrimaryKey",
						$this->dbAdapter->protect_field_names($relDef['linkForeignField']),
						array(
							'WHERE '.$this->dbAdapter->protect_field_names($relDef['linkTable']).'.'
							.$this->dbAdapter->protect_field_names($relDef['linkLocalField'])
							.'=? '.$condAddition.(empty($relDef['orderBy'])?'':' ORDER BY '.$relDef['orderBy']),
							$localFieldVal,
							$a[1]
						)
					);
					$this->dbAdapter->freeResults();
					return abstractModel::getMultipleModelInstances($relDef['modelName'],empty($PKs)?array():$PKs);
				}
			}
			return $this->getRelated($relName);
		}

		#- manage setter/getter for datas ([gs]et_field|[gs]etField) case sensitive
		if( preg_match('!^([gs]et)_?('.self::$_internals[$className]['datasKeyExp'].')$!',$m,$match) ){
			if($match[1]==='get'){
				return $this->datas[self::_cleanKey($this,'datas',$match[2])];
			}else{
				array_unshift($a,self::_cleanKey($this,'datas',$match[2]));
				call_user_func_array(array($this,'_setData'),$a);
				return $this;
			}
		}

		#- nothing left throw an exception
		throw new BadMethodCallException("$className trying to call unknown method $m.");
	}

	###--- FILTER RELATED METHODS ---###
	/**
	* internal method to get exact keys on magic methods call such as getRelName
	* @param string $keyType one or many of hasOne|hasMany|datas|datasDefs
	* @return string clean key or false if not find
	*/
	static public function _cleanKey($modelName,$keyType,$k){
		if( strpos($keyType,'|') ){
			$keyType=explode('|',$keyType);
			foreach($keyType as $type){
				if( false!==($key=self::_cleanKey($modelName,$type,$k)) )
					return $key;
			}
			return false;
		}
		if( $keyType === 'datas')
			$keyType = 'datasDefs';
		$datas = self::_getModelStaticProp($modelName,$keyType);
		if( isset($datas[$k]) )
			return $k;
		#- try to lower first char first
		$k = strtolower($k[0]).substr($k,1);
		if( isset($datas[$k]) )
			return $k;
		#- try to upper first char
		$k = ucfirst($k);
		if( isset($datas[$k]) )
			return $k;
		#- nothing worked we try a last check on primaryKey
		if( $k==='PK')
			return self::_getModelStaticProp($modelName,'primaryKey');
		return false;
	}

	/**
	* set multiples model datas values  at once from an array.
	* @param array  $datas              array of key value pair of datas to set.
	*                                   unknown keys or keys corresponding to primarykey will just be ignored.
	*                                   Valid keys are datasDefs keys, has(One|Many) keys and finally any key that have a setter defined
	* @param bool   $bypassFilters      if true then will bypass datas filtering and user defined setters but will restore $this->bypassFilters to it's previous state
	* @param mixed  $forcedPrimaryKey   you should NEVER use this parameter outside abstractModel::__construct().
	*                                   as it will break the self::instances key integrity!
	*                                   in fact the only reason to use this at this time is to permit modelAddons to
	*                                   set primaryKey during the modelConstruction phase.
	* @param bool   $leaveNeedSaveState by default setting datas will set $this->needSave to 1, setting this parameter to true
	*                                   will leave $this->needSave to its previous state.
	* @return $this for method chaining, you can check filtersMsgs after that call to know if there's some errors
  * @note this method is prepend with a '_' to allow you to still have user define setter for an eventual field named datas (who knows you can need it)
 	*/
	public function _setDatas($datas,$bypassFilters=false,$forcedPrimaryKey=null,$leaveNeedSaveState=false){
		$datasDefs = self::_getModelStaticProp($this,'datasDefs');
		$hasMany = self::_getModelStaticProp($this,'hasMany');
		$hasOne = self::_getModelStaticProp($this,'hasOne');
		$primaryKey = self::_getModelStaticProp($this,'primaryKey');
		$filtersState = $this->bypassFilters;
		if(false !== $leaveNeedSaveState )
			$leaveNeedSaveState = $this->needSave;
		if($bypassFilters)
			$this->bypassFilters = true;
		foreach($datas as $k=>$v){
			if(  $k===$primaryKey)
				continue;
			if( isset($datasDefs[$k]) ){
				if( $this->bypassFilters)
					$this->datas[$k] = self::setModelDatasType($this,$k,$v);//$v;
				else
					$this->{$k} = $v;
			}elseif( isset($hasOne[$k]) ){
				$this->{$k} = $v;
			}elseif(isset($hasMany[$k])){
				$this->{'set'.$k.'Collection'}($v);
			}elseif( $this->_methodExists('set'.$k,false,true)){
				$this->{'set'.$k}($v);
			}
		}
		$this->bypassFilters = $filtersState;
		if( null !== $forcedPrimaryKey )
			$this->datas[$primaryKey] = self::setModelDatasType($this,$primaryKey,$forcedPrimaryKey);
		if(false !== $leaveNeedSaveState )
			$this->needSave = $leaveNeedSaveState;
		return $this;
	}


	/**
	* same as _setDatas but for a unique data key.
	* @param string $key    datas key to set
	* @param mixed  $value  value to set (will be convert to correct type)
	* @param bool   $bypassFilters      if true then will bypass datas filtering and user defined setters but will restore $this->bypassFilters to it's previous state
	* @param bool   $leaveNeedSaveState by default setting datas will set $this->needSave to 1, setting this parameter to true
	*                                   will leave $this->needSave to its previous state.
	*
	*/
	public function _setData($key,$value,$bypassFilters=false,$leaveNeedSaveState=false){
		$filterState = $this->bypassFilters;
		$datasDefs = self::_getModelStaticProp($this,'datasDefs');
		if(false !== $leaveNeedSaveState )
			$leaveNeedSaveState = $this->needSave;
		if($bypassFilters)
			$this->bypassFilters=true;
		$this->__set($key,$value);
		$this->bypassFilters = $filterState;
		if(false !== $leaveNeedSaveState )
			$this->needSave = $leaveNeedSaveState;
		return $this;
	}

	/**
	* setType of all datas in model at once (called at __construct time)
	*/
	public function setModelDatasTypes(){
		$datasDefs  = self::_getModelStaticProp($this,'datasDefs');
		$primaryKey = self::_getModelStaticProp($this,'primaryKey');
		foreach($datasDefs as $k=>$v){
			if( $k===$primaryKey && $this->isTemporary() )
				continue;
			if( $this->datas[$k] === null )
				continue;
			self::_setType($this->datas[$k],$datasDefs[$k]['Type']);
		}
	}
	/**
	* setType of one datas Key in model (call at __set time) according to types
	* defined in datasDefs
	* @param string $modelName
	* @param string $key
	* @param mixed  $value passed by reference
	* @return $value with type setted
	*/
	static public function setModelDatasType($modelName,$key,&$value=null){
		if($value===null)
			return null;
		$datasDefs = self::_getModelStaticProp($modelName,'datasDefs');
		if(! isset($datasDefs[$key]) )
			throw new OutOfBoundsException((is_object($modelName)?get_class($modelName):$modelName)."::setModelDatasType() $key is not a valid datas key.");
		return self::_setType($value,$datasDefs[$key]['Type']);
	}
	/**
	* internal method to setType from database type definition
	* @param mixed  $value   passed by reference
	* @param string $typeStr as given in create Table SQL statement
	* @return mixed value with the required type setted
	*/
	static protected function _setType(&$value,$typeStr){
		if( preg_match('!^\s*((tiny|big|medium|small)?int|timestamp)!i',$typeStr))
			$type = 'int';
		elseif(preg_match('!^\s*(float|real|double|decimal)!i',$typeStr))
			$type = 'float';
		else
			$type = 'string';
		settype($value,$type);
		return $value;
	}

	/**
	* apply filters to datas fields.
	* First will look for a filterFieldName method wich has to return filtered value or false if not passed.
	* filterFieldName methods has to call appendFilterMsg() method themselves.
	* If no filter method is found then will look in the static $filters property @see abstractModel::$filters
	* and if found then will return given filtered value or false if not succeed and then append a filterMsg.
	* The second way is not recommanded anymore and may be dropped in future version and replaced by the filtersModelAddon.
	* @param string $k the field to be set
	* @param mixed  $v the value to be set
	* @return mixed or false in case of error.
	*/
	public function filterData($k,$v){
		// if a filterField method exists we use it
		if( $this->_methodExists("filter$k",false,true) ){
			return $this->{"filter$k"}($v);
		}
		//-- try old way fashioned filters property 
		$filters = self::_getModelStaticProp($this,'filters');
		#- if no filtering methods was found at all then just return value
		if( empty($filters[$k]) ){
			return $v;
		}
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
			$v = func_get_arg(1);
			$this->appendFilterMsg($msg?$msg:"invalid value(%s) given for %s",array($v,$k));
			return false;
		}
		return $v;
	}

	/**
	* append a message to the filterMsg stack
	* @param string $msg message or langManager idMessage to append
	* @param array  $sprintfDatas data substitution to make in msg using sprintf
	* @return $this for method chaining
	*/
	public function appendFilterMsg($msg,array $sprintfDatas=null){
		if( isset($this->filtersDictionary) ){
			$msg = langManager::msg($msg,$sprintfDatas,$this->filtersDictionary);
		}elseif( null !== self::$dfltFiltersDictionary ){
			$msg = langManager::msg($msg,$sprintfDatas,self::$dfltFiltersDictionary);
		}elseif(! empty($sprintfDatas)){
			array_unshift($sprintfDatas,$msg);
			$msg = call_user_func_array('sprintf',$sprintfDatas);
		}
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

	/**
	*
	* @param string $modelName name of model to check for existing datas
	* @param string $fieldName name of the field we want to make the check on
	* @param mixed  $value     value we want to check for existance
	* @param bool   $returnInstance[optional] whether to return a bool or a modelInstance
	* @param mixed  $ignoredPK[optional] instances PK to ignore (may be one PK a list of PK or a modelCollection with ignored elements in it)
	* @return bool or abstractModel instance depending on $returnInstance
	*/
	static public function modelCheckFieldDatasExists($modelName,$fieldName,$value,$returnInstance=false,$ignoredPK=null){
		$db = self::getModelDbAdapter($modelName);
		$fieldName = $db->protect_field_names($fieldName);
		$w = array("WHERE $fieldName = ?",$value);
		if($ignoredPK!==null){
			if( $ignoredPK instanceof modelCollection)
				$ignoredPK = $ignoredPK->PK;
			if( is_array($ignoredPK))
				$w = array("WHERE $fieldName = ? AND ".self::_getModelStaticProp($modelName, 'primaryKey').' NOT IN (?)',$value,$ignoredPK );
			else
				$w = array("WHERE $fieldName = ? AND ".self::_getModelStaticProp($modelName, 'primaryKey').' != ?',$value,$ignoredPK );
		}
		$PK = $db->select_value(self::_getModelStaticProp($modelName, 'tableName'),self::_getModelStaticProp($modelName, 'primaryKey'),$w);
		if( $PK===false)
			return $returnInstance?null:false;
		return $returnInstance? self::getModelInstance($modelName, $PK):true;
	}
	###--- SOME WAY TO DEAL WITH THE MISSING STATIC LATE BINDING (will probably change with PHP >= 5.3 ---###
	/**
	* quick and dirty "hack" to permit access to static methods and property of models
	* waiting for php >= 5.3 late static binding implementation
	* @param mixed $modelName string modelName or model Instance
	* @param string $staticProperty string requested static property name
	* @return mixed depending on the requested property or null if not exists
	*/
	static public function _getModelStaticProp($modelName,$staticProperty){
		if( is_object($modelName) )
			$modelName = get_class($modelName);
		if(empty(self::$_internals[$modelName]))
			self::_initInternals($modelName);
		return eval("return isset($modelName::\$$staticProperty)?$modelName::\$$staticProperty:null;");
	}
	static public function _makeModelStaticCall($modelName,$method){
		if( $modelName instanceof abstractModel )
			$modelName = self::_getModelStaticProp($modelName,'modelName');
		if(func_num_args() <= 2)
			return call_user_func("$modelName::$method");
		$args = func_get_args();
		$args = array_slice($args,2);
		return call_user_func_array("$modelName::$method",$args);
	}
	static public function getModelDbAdapter($modelName){
		if($modelName instanceof abstractModel)
			return $modelName->dbAdapter;
		$db = db::getInstance(self::_getModelStaticProp($modelName,'dbConnectionDescriptor'));
		return self::$useDbProfiler? new dbProfiler($db) : $db;
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
				throw new UnexpectedValueException("$modelName::hasRelated('$relType') Invalid value for parameter relType");
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

	/**
	* return a list of supported modelAddons
	* @param mixed $modelName string modelName or model Instance
	* @return array;
	*/
	static public function _modelGetSupportedAddons($modelName){
		return self::_getModelStaticProp($modelName,'modelAddons');
	}
	/**
	* check if a model support given modelAddon.
	* @param mixed $modelName       string modelName or model Instance
	* @param mixed $modelAddon      string modelAddonName or modelAddon Instance
	* @param bool  $caseInsensitive by default lookup is done in a case sensitive way, setting this to true will do it in a case insensitive way.
	* @return bool
	*/
	static public function _modelSupportsAddon($modelName,$modelAddon,$caseInsensitive=false){
		$supported = self::_modelGetSupportedAddons($modelName);
		if( is_object($modelAddon) )
			$modelAddon = get_class($modelAddon);
		$modelAddon = preg_replace('!_?[mM]odelAddon$!','',$modelAddon);
		if( $caseInsensitive ){
			$modelAddon = strtolower($modelAddon);
			$supported = array_map('strtolower',$supported);
		}
		return in_array($modelAddon,$supported,true);
	}
	/**
	* check that current instance supports given modelAddon.
	* @param mixed $modelAddon  string modelAddonName or modelAddon Instance
	* @param bool  $returnAddon set true to return modelAddon instance instead of true on success and null instead of false on fail
	* @return mixed bool or modelAddon depend on $returnAddon parameter
	*/
	public function supportsAddon($modelAddon,$returnAddon = false){
		if( isset($this->_modelAddons[$modelAddon]) ){
			return $returnAddon?$this->_modelAddons[$modelAddon]:true;
		}
		return false;
	}

	###--- COMMON METHODS ---###
	/**
	* return a count of given model in database table.
	* @param string $modelName model name you want count for
	* @param array  $filter   same as conds in class-db permit you to count filtered models
	* @return int or false on error
	*/
	static public function getModelCount($modelName,$filter=null){
		$tableName = self::_getModelStaticProp($modelName,'tableName');
		$count = self::getModelDbAdapter($modelName)->select_single_value($tableName,'count(*)',$filter);
		return $count===false?0:(int) $count;
	}

	/**
	* optionnal method to let user define it's own primaryKey generation algorythm.
	* (generally used when autoIncrement is not set on the primaryKey)
	* @return primaryKey
	* @private
	* @return mixed
	* @fn protected function _newPrimaryKey(){}
	*/

	/**
	* optionnal method onBeforeSave to let user define any action to take before the save to start
	* if return true then abort the save process without any warning in the save method.
	* So the user can choose to throw an exception or to append messages to any stack messages or any choice of his own
	* @private
	* @fn protected function onBeforeSave(){}
	*/
	/**
	* optionnal method onAfterSave to let user define any action to take after the save completion
	* @param bool $wasTemporary
	* @private
	* @fn protected function onAfterSave($wasTemporary){}
	*/
	/**
	* Optionnal method onBeforeDelete to let user define any action to take before the delete to start
	* If return true then abort the delete process without any warning in the delete method.
	* So the user can choose to throw an exception or to append messages to any stack messages or any choice of his own.
	* If the method return true it MUST set $this->deleted to true and call $this->detach() method if required.
	* @private
	* @fn protected function onBeforeDelete(){}
	*/
	/**
	* Optionnal method onAfterDelete to let user define any action to take after the delete completion
	* @private
	* @fn protected function onAfterDelete(){}
	*/


	/**
	* save the Model to database. throw an exception on error.
	* @return $this for method chaining. (throw exception on error)
	*/
	public function save(){
		if($this->deleted)
			throw new RuntimeException(get_class($this)."::save($this->PK) Can't save a deleted model");
		$needSave = $this->needSave;
		# exit if already in saving state
		if( $needSave < 0 )
			return $this;
		if( $needSave > 0 && $this->_methodExists('onBeforeSave') ){
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
		$oneRelDefs = self::_getModelStaticProp($this,'hasOne');
		$manyRelDefs = self::_getModelStaticProp($this,'hasMany');
		foreach($oneRelDefs as $relName=>$relDef){
			switch($relDef['relType']){
				case 'dependOn': #- we dependOn that one so must save it first
					if(! isset($this->_oneModels[$relName])){
						#- if we already have a value set in or a default one we can goes on else throw an exception
						if(! isset($relDef['localField'])){
							throw new RuntimeException(get_class($this)."::save() require $relName to be set."); # must be an object
						}else{
							$default = $datasDefs[$relDef['localField']]['Default'];
							if( $default !== $this->datas[$relDef['localField']] )
								continue; #- value already set to something we consider here that the value was previously set or at least that user have correctly set this
							throw new RuntimeException(get_class($this)."::save() require $relName to be set.");
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
		foreach($manyRelDefs as $relName=>$relDef){
			if(! empty($relDef['linkTable']) ){ #- save object that use a link table at the very end
				if( isset($this->_manyModels[$relName]) )
					$linked[$relName] = $relDef;
				continue;
			}
			switch($relDef['relType']){
				case 'dependOn': #- we dependOn that one so must save it first
					if(! isset($this->_manyModels[$relName]) )
						throw new RuntimeException(get_calss($this)."::save() require at least one $relName to be set.");
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
		$wasTemporary = $this->isTemporary();
		$primaryKey = self::_getModelStaticProp($this,'primaryKey');
		$tableName = self::_getModelStaticProp($this,'tableName');
		if( $needSave > 0){
			$datas = $this->datas;
			$PK = $this->PK;
			unset($datas[$primaryKey]); # update all but primaryKey
			if(! $wasTemporary ){ # update
				if( false === $this->dbAdapter->update($tableName,$datas,array('WHERE '.$this->dbAdapter->protect_field_names($primaryKey).'=?',$PK)) )
					throw new ErrorException(get_class($this)." Error while updating (PK=$PK).");
			}else{ # insert
				# check for user define primaryKey generation
				if(! $this->_methodExists('_newPrimaryKey') ){ # database manage key generation (autoincrement)
					$this->datas[$primaryKey] = $this->dbAdapter->insert($tableName,$datas);
					if( $this->datas[$primaryKey] === false )
						throw new ErrorException(get_class($this)." Error while saving (PK=$PK).");
				}else{ # user define key generation
					$datas[$primaryKey] = $nPK = $this->_newPrimaryKey();
					if( $this->dbAdapter->insert($tableName,$datas,false) === false )
						throw new ErrorException(get_class($this)." Error while saving (PK=$nPK).");
					$this->datas[$primaryKey] = $nPK;
				}
				#- reset temporary instance Key
				self::_setInstanceKey($this,$PK);
			}
		}

		#- then save models that weren't saved
		foreach($waitForSave as $relName){
			if( ! $wasTemporary ){
				$this->getRelated($relName)->save();
				continue;
			}
			#- attempt to set reverse relation where it make sense
			#- we must tell related that they've a back relation (if it exists)
			if( isset($oneRelDefs[$relName]) ){
				$lclFld  = isset($oneRelDefs[$relName]['localField'])?$oneRelDefs[$relName]['localField']:null;
				$frgnFld = isset($oneRelDefs[$relName]['foreignField'])?$oneRelDefs[$relName]['foreignField']:null;
				// if no localField or localField is PK and fwe point to a field in the related table we suppose that we have to set the new primaryKey in this foreignField
				if( (empty($lclFld) || $lclFld===$primaryKey) && (!empty($frgnFld) && $frgnFld!==self::_getModelStaticProp($oneRelDefs[$relName]['modelName'],'primaryKey')) ){
					$this->getRelated($relName)->_setData($frgnFld,$this->PK);
				}
			}else if(isset($manyRelDefs[$relName]) ){
				$lclFld  = isset($manyRelDefs[$relName]['localField'])?$manyRelDefs[$relName]['localField']:null;
				$frgnFld = isset($manyRelDefs[$relName]['foreignField'])?$manyRelDefs[$relName]['foreignField']:null;
				// if no localField or localField is PK and fwe point to a field in the related table we suppose that we have to set the new primaryKey in this foreignField
				if( (empty($lclFld) || $lclFld===$primaryKey) && (!empty($frgnFld) && $frgnFld!==self::_getModelStaticProp($manyRelDefs[$relName]['modelName'],'primaryKey')) ){
					$this->getRelated($relName)->_setData($frgnFld,$this->PK);
				}
			}
			$this->getRelated($relName)->save();
		}
		#- the linked part may certainly be optimized for better performance but this should work for now
		foreach($linked as $relName=>$relDef){
			$related = $this->getRelated($relName);
			$needOptimize = $this->dbAdapter->delete($relDef['linkTable'],array('WHERE '.$this->dbAdapter->protect_field_names($relDef['linkLocalField']).'=?',$this->PK));
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
		if( $needSave > 0 && $this->_methodExists('onAfterSave') ){
			$res = $this->onAfterSave($wasTemporary);
		}
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
			throw new RuntimeException(get_class($this)."::delete($this->PK) model already deleted");
		$needSave = $this->needSave;
		if($needSave < 0) //- already performing deletion
			return $this;
		if( $this->_methodExists('onBeforeDelete') ){
			$res = $this->onBeforeDelete();
			if( true === $res )
				return;
		}
		$this->needSave = -1;
		#- check one related objects
		foreach(self::_getModelStaticProp($this,'hasOne') as $relName=>$relDef){
			switch($relDef['relType']){
				case 'requiredBy': #- related require current so we delete it
					if( $this->{$relName} instanceof $relDef['modelName'] ) #- ensure a related model exists before deleting it
						$this->{$relName}->delete();
					break;
				case 'ignored':
					if(! $this->{$relName} instanceof $relDef['modelName'] )
						break;
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
				$this->dbAdapter->delete($relDef['linkTable'],array('WHERE '.$this->dbAdapter->protect_field_names($relDef['linkLocalField']).'=?',$this->PK));
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
						$rels->save();
					}
				break;
			}
		}//*/
		$tableName  = self::_getModelStaticProp($this,'tableName');
		$primaryKey = self::_getModelStaticProp($this,'primaryKey');
		$res = $this->dbAdapter->delete($tableName,array('WHERE '.$this->dbAdapter->protect_field_names($primaryKey).'=?',$this->PK));
		if($res===false)
			throw new ErrorException(get_class($this)."::delete() Error while deleting.");
		if( $this->_methodExists('onAfterDelete') ){
			$this->onAfterDelete();
		}
		$this->deleted = true;
		$this->detach();
	}

	/**
	* check if model already exists in database or not (in fact check for a temporary primaryKey)
	* @return bool
	*/
	public function isTemporary(){
		return preg_match('!^abstractModelTmpId!',$this->PK)?true:false;
	}
	/*
	* check whether model is part of the given collection or not
	* @see modelCollection::hasModel()
	* @return bool
	*/
	public function inCollection(modelCollection $collection){
		if( ! $this instanceof $collection->collectionType ){
			throw new InvalidArgumentException(get_class($this).'::inCollection expect '.$this->modelName.'Collection as parameter');
		}
		return isset($collection[$this->PK])?true:false;
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
	* You can think it may also be used to have multiple instance for the same model with same PK but you should better use clone object for this purpose!
	* (I don't see any good reason to use this in other place but who know perhaps in some case it can be usefull, please let me know)
	* @note if you have other variables that point to the same instance of model they will be detached too
	*/
	public function detach(){
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
	static public function destroy(abstractModel &$modelInstance){
		$modelInstance->detach();
		$modelInstance = null;
	}

	/**
	* Render the model as string. If no abstractModel::$__toString property is defined a generic string will be rendered identifying the tyme and key of the model.
	* You can set the way the model will be rendered as a string by passing a $formatStr as first parameter.
	* If $formatStr is null then the method will look for a default abstractModel::$__toString property as a default formatStr to use.
	* @param string $formatStr format string to use to render the model as a string.
	*                          the generic syntax of this string is the same as HEREDOC syntax.
	*                          Additionnaly you can refer to any model properties as %propertyName
	*                          or even do some expression evaluation using the syntax %{expression}%
	*                          for example: "%modelName(%PK) is a %{preg_match('!tmp!',%PK)?'temporary instance':'database instance'}%"
	*                          will return something like this: "modelName(1) is a database instance"
	*                          to display a litteral '%' character please just double it like '%%'
	* @return string
	*/
	public function _toString($formatStr=null){
		$format = $formatStr!==null ? $formatStr : self::_getModelStaticProp($this,'__toString');
		if( empty($format) )
			return "“ instance of model $this->modelName with primaryKey $this->primaryKey=$this->PK ”";
		$string = preg_replace(array('/(?<!%|\\\\)%(?!%)([A-Za-z_][A-Za-z0-9_]*)/','/(?<!%|\\\\)%{(.*?)}%(?!%)/s','![\\\\%]%!'),array('$this->\\1',"\n__TOSTRING\n.(\\1).<<<__TOSTRING\n",'%'),$format);
		return eval('return<<<__TOSTRING'."\n$string\n__TOSTRING;\n");
	}
	//-- backward compatibility with older versions
	public function __toString(){
		$formatStr = func_num_args()?func_get_arg(0):null;
		try{
			return call_user_func(array($this,'_toString'),$formatStr);
		}catch(Exception $e){
			return "Exception thrown in  ".get_class($this)."::__toString(): ".$e->getMessage();
		}
	}
}
