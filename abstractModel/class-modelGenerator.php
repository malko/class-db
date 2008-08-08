<?php
/**
* @package class-db
* @subpackage abstractModel
* @author Jonathan Gotti <jgotti at jgotti dot org>
* @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
* @since 2007-10
* @class modelGenerator
* @changelog
*            - 2008-08-05 - add empty property __toString for model rendering as string
*            - 2008-08-04 - add additional methods proposition for filtering enums fields
*            - 2008-05-08 - add modelAddons property to final class
*            - 2008-03-30 - separation of generator and the rest of code
*                         - now generator conform to new relation definition as hasOne / hasMany
*                           instead of one2one / one2many / many2many
*                         - remove withRel parameter
*            - 2008-03-23 - better model generation :
*                           * support autoMapping
*                           * can overwrite / append / or skip existing models
*                           * can set a constant as dbConnectionStr
* @code
* // instanctiate a generator
* $g = new modelGenerator('mysqldb://test;localhost;root;','models');
* // set the generator to overwrite base class if they already exists
* $g->onExist = 'o';
* // then let the generator do his work
* $g->doGeneration('DB_CONNECTION_CONSTANT_STRING_TO_USE','testModel');
* @endcode
*/

require_once(dirname(__file__).'/class-db.php');

class modelGenerator{
	public $connectionStr = '';
	/** class-db active instance */
	protected $db = null;
	/** fullpath to output directories */
	public $outputDir = '/tmp/';

	/**
	* do we try to auto map relationships at generation time.
	* be sure to correctly check the results when doing this, automatic generators are really
	* far from perfect, it will handle some case well, forgive some and worst can mistake some other
	* Try this on and if you think thats not good enough turn this off
	* @see coming next documentation on how does this work.
	*/
	public $autoMap = true;
	/**
	* what to do when a given model file is found (extended models are always skip when exists).
	*  - o => overwrite existing models but leave extended models as is
	*  - a => append the new model to the existing one you must have a look to files and delete one of the generated class
	*     can be usefull on updates to check relationships by hands for example. (this is the default behaviour)
	*     leave extended models as is
	*  - s => skip generation of this model so only new models are generated usefull when you add some tables to your database
	*/
	public $onExist = 'a';

	/**
	* create an instance of modelGenerator.
	* @param string $dbConenctionStr
	* @param string $outputDir
	* @param int    $dbVerboseLevel
	*/
	function __construct($dbConnectionStr,$outputDir=null,$dbVerboseLevel=0){
		$this->connectionStr = $dbConnectionStr;
		$this->db = db::getInstance($this->connectionStr);
		$this->db->beverbose = (int) $dbVerboseLevel;
		if(! is_null($outputDir) )
			$this->outputDir = substr($outputDir,-1)==='/'?$outputDir:$outputDir.'/';
		if(! is_dir($this->outputDir) ){
			if( false===mkdir($this->outputDir,0770,true))
				throw new Exception("Can't create output dir $this->outputDir.");
		}
	}

	/**
	* generate class for each table in database.
	* @param string $prefix               add a prefix to generated models
	* @param string $dbConnectionDefined  an optionnal defined dbConnectionStr to use as a replacement for connectionStr
	*                                     really usefull when you want to change the database connection string without
	*                                     editing all models.
	*/
	function doGeneration($dbConnectionDefined=null,$prefix='model'){
		#- get table list
		$tables  = $this->db->list_tables();
		if(! $tables)
			return false;
		#- get singles names
		foreach($tables as $tb)
			$singles[$tb] = preg_replace("!(s|x)$!","",$tb);
		#- then fields info for each tables
		foreach($tables as $tb){
			$tbFields[$tb] = $this->db->list_table_fields($tb,true);
			foreach($tbFields[$tb] as $fK=>$fInfos){
				if(strpos($fInfos['Key'],'PRI')===0){
					$tbFields[$tb]['PK'] = $fInfos['Field'];
					break;
				}
			}
		}
		$relMatch=array(
			'requiredBy' => 'dependOn',
			'ignored'    => 'ignored',
			'dependOn'   => 'requiredBy',
		);
		if( $this->autoMap ){
			#- when we have all datas we start automapping of relationships
			# start with one2one
			foreach($tbFields as $tb=>$fields){
				foreach($fields as $fK=>$fInfos){
					if($fK==='PK') continue;
					$fName = $fInfos['Field'];
					$relDflt = false;
					#- hasOne relations
					if( ($foreignTb = array_search($fName,$singles))){
						if(! empty($tbFields[$foreignTb]['PK'] ) ){
							$hasOne=array(
								'modelName'=>$foreignTb,
								'localField'=>$fName,
								#- ~ 'foreignField'=>$tbFields[$foreignTb]['PK'], // foreign Field is PK just ignore it
							);
							if( $fInfos['Default']!=='' || $fInfos['Null'] === 'YES'){
								$hasOne['relType'] = "ignored";
								$relDflt = $fInfos['Default']===''?null:$fInfos['Default'];
							}else{
								$hasOne['relType'] = "dependOn";
							}
							$tbFields[$tb]['hasOne'][$fName] = $hasOne;
						}else{
							$foreignTb = false;
						}
					}elseif( in_array($fName,$tables) ){
						if(! empty($tbFields[$fName]['PK'] ) ){
							$foreignTb = $fName;
							#- ~ $tbFields[$tb]['one2one'][$fName] = $foreignTb ;
							$hasOne=array(
								'modelName'=>$foreignTb,
								'localField'=>$fName,
								#- ~ 'foreignField'=>$tbFields[$foreignTb]['PK'], // foreign Field is PK just ignore it
							);
							if( $fInfos['Default']!=='' || $fInfos['Null'] === 'YES'){
								$hasOne['relType'] = "ignored";
								$relDflt = $fInfos['Default']===''?null:$fInfos['Default'];
							}else{
								$hasOne['relType'] = "dependOn";
							}
							$tbFields[$tb]['hasOne'][$fName] = $hasOne;
						}
					}

					#- if we've found a hasOne map the corresponding hasOne or hasMany
					if( $foreignTb && !empty($tbFields[$tb]['PK']) ){
						#- ~ $tbFields[$foreignTb]['one2many'][$tb] = array($tbFields[$foreignTb]['PK'],$fName);
						if($fInfos['Key'] === 'UNI' || $fInfos['Key'] === 'PRI'){
							$tbFields[$foreignTb]['hasOne'][$tb] = array(
								'modelName'   =>$tb,
								#- ~ 'localField'  =>$tbFields[$foreignTb]['PK'],
								'foreignField'=>$fName,
								'relType'     => $relMatch[$hasOne['relType']]
							);
							if($relDflt!==false)
								$tbFields[$foreignTb]['hasOne'][$tb]['foreignDefault'] = $relDflt;
						}else{
							$tbFields[$foreignTb]['hasMany'][$tb] = array(
								'modelName'   =>$tb,
								#- ~ 'localField'  =>$tbFields[$foreignTb]['PK'],
								'foreignField'=>$fName,
								'relType'     => $relMatch[$hasOne['relType']]
							);
							if($relDflt!==false)
								$tbFields[$foreignTb]['hasMany'][$tb]['foreignDefault'] = $relDflt;
						}
					}
				}

				#- now try to manage many2many links (only simple correspondance table with two fields at the moment
				if( empty($fields['PK']) && isset($tbFields[$tb]['hasOne']) && count($tbFields[$tb]['hasOne'])===2 ){
					$m2ms = array();
					foreach($tbFields[$tb]['hasOne'] as $relDef)
						$m2ms[] = $relDef;
					#- ~ $tbFields[$m2ms[0][0]]['many2many'][$m2ms[1][0]] = array($tb,$m2ms[0][1],$m2ms[1][1]);
					#- ~ $tbFields[$m2ms[1][0]]['many2many'][$m2ms[0][0]] = array($tb,$m2ms[1][1],$m2ms[0][1]);
					#- @todo manage the fact that one can be a hasOne instead of hasMany (even if it's weird to manage it that way)
					$tbFields[$m2ms[0]['modelName']]['hasMany'][$m2ms[1]['modelName']] = array(
						'modelName'       => $m2ms[1]['modelName'],
						'linkTable'       => $tb,
						#- ~ 'localField'      => $m2ms[0]['foreignField'], //must be model primaryKey
						'linkLocalField'  => $m2ms[0]['localField'],
						#- ~ 'foreignField'    => $m2ms[1]['foreignField'], // must be model PrimaryKey
						'linkForeignField'=> $m2ms[1]['localField'],
						'relType'         => 'ignored'
					);
					$tbFields[$m2ms[1]['modelName']]['hasMany'][$m2ms[0]['modelName']] = array(
						'modelName'       => $m2ms[0]['modelName'],
						'linkTable'       => $tb,
						#- ~ 'localField'      => $m2ms[1]['foreignField'], //must be model primaryKey
						'linkLocalField'  => $m2ms[1]['localField'],
						#- ~ 'foreignField'    => $m2ms[0]['foreignField'], // must be model PrimaryKey
						'linkForeignField'=> $m2ms[0]['localField'],
						'relType'         => 'ignored'
					);
				}
			}
		}

		#- generate models
		foreach($tbFields as $tbName=>$modelDesc)
			$this->createModel($tbName,$modelDesc,$dbConnectionDefined,$prefix);
	}


	function createModel($tableName,array $modelDesc=null,$dbConnectionDefined=null,$prefix='model'){
		#- try to auto detect modelDescription if not given
		if( empty($modelDesc) ){
			$modelDesc = $this->db->list_table_fields($tableName,true);
			if(! $modelDesc )
				return false;
		}

		#- lookup for primary key if not set
		if(! isset($modelDesc['PK']) ){
			foreach($modelDesc as $f){
				if((! empty($f['Key'])) && strrpos($f['Key'],'PRI')===0){
					$modelDesc['PK'] = $f['Field'];
					break;
				}
			}
			if( empty($modelDesc['PK']) )
				return false; # we don't manage models without PK
		}

		#- initVars
		$methods = $datas = $datasTypes = $one2one = $one2many = $many2many = array();

		if( is_null($dbConnectionDefined) )
			$dbConnectionDefined = "'$this->connectionStr'";

		#- prepare the datas array
		foreach( $modelDesc as $k=>$f){
			if( in_array($k,array('PK','hasMany','hasOne'),true))
				continue;
			if( strrpos($f['Key'],'PRI')===0 && $f['Default'] === null )
				$f['Default'] = 0;

			$datas[]       = "'$f[Field]' => '$f[Default]', // $f[Type];";
			$datasTypes[]  = "'$f[Field]' => array('Type'=>'".str_replace("'","\'",$f['Type'])."', 'Extra'=>'$f[Extra]', 'Null' =>'$f[Null]', 'Key' =>'$f[Key]', 'Default'=>'$f[Default]'),";
			if( preg_match('!^\s*ENUM\s*\((.*)\)\s*$!i',$f['Type'],$m)){
				$vals = $m[1];
				$methods[] = "
	public function filter".ucFirst($f['Field'])."(\$val){
		\$vals=array($vals);
		if(! in_array(\$val,\$vals)){
			\$this->appendFilterMsg('invalid $f[Field] value');
			return false;
		}
		return \$val;
	}";
			}
		}

		#- ~ prepare hasOne
		$hasOnes[] = "// 'relName'=> array('modelName'=>'modelName','relType'=>'ignored|dependOn|requireBy',['localField'=>'fldNameIfNotPrimaryKey','foreignField'=>'fldNameIfNotPrimaryKey','foreignDefault'=>'ForeignFieldValueOnDelete'])";
		if(! empty($modelDesc['hasOne']) ){
			foreach($modelDesc['hasOne'] as $relName=>$hasOne){
				$oneStr = '';
				foreach($hasOne as $k=>$v){
					$oneStr .= "\n\t\t\t'$k'=>".($v===null?'null':"'".($k==='modelName'?self::__prefix($v,$prefix):$v)."'").",";
				}
				$hasOnes[] = "'$relName' => array($oneStr\n\t\t),";
			}
		}
		#- ~ prepare hasMany
		$hasManys[] = "//   'relName'=> array('modelName'=>'modelName','relType'=>'ignored|dependOn|requireBy','foreignField'=>'fieldNameIfNotPrimaryKey'[,'localField'=>'fieldNameIfNotPrimaryKey','foreignDefault'=>'ForeignFieldValueOnDelete','orderBy'=>'orderBy'=>'fieldName [asc|desc][,fieldName [asc|desc],...]']),";
		$hasManys[] = "//or 'relName'=> array('modelName'=>'modelName','linkTable'=>'tableName','linkLocalField'=>'fldName','linkForeignField'=>'fldName','relType'=>'ignored|dependOn|requireBy',['orderBy'=>'fieldName [asc|desc][,fieldName [asc|desc],...]']),";
		if(! empty($modelDesc['hasMany']) ){
			foreach($modelDesc['hasMany'] as $relName=>$hasMany){
				$manyStr = '';
				foreach($hasMany as $k=>$v)
					$manyStr .= "\n\t\t\t'$k'=>".($v===null?'null':"'".($k==='modelName'?self::__prefix($v,$prefix):$v)."'").",";
				$hasManys[] = "'$relName' => array($manyStr\n\t\t),";
			}
		}

		$modelName = self::__prefix($tableName,$prefix);

		$str = "<?php
/**
* autoGenerated on ".date('Y-m-d')."
* @package models
* @subpackage $prefix
* @class BASE_$modelName
*/
class BASE_$modelName extends abstractModel{

	protected \$datas = array(
		".implode("\n\t\t",$datas)."
	);

	static protected \$filters = array();

	static protected \$hasOne = array(
		".implode("\n\t\t",$hasOnes)."
	);
	static protected \$hasMany = array(
		".implode("\n\t\t",$hasManys)."
	);

	/** database link */
	protected \$dbConnectionDescriptor = $dbConnectionDefined;
	protected \$dbAdapter = null;

	static protected \$modelName = 'BASE_$modelName';
	static protected \$tableName = '$tableName';
	static protected \$primaryKey = '$modelDesc[PK]';

	/**
	* field information about type, default values and so on
	*/
	static protected \$datasDefs = array(
		".implode("\n\t\t",$datasTypes)."
	);

	static public function getNew(){
		return abstractModel::getModelInstance('$modelName');
	}

	static public function getInstance(\$PK=null){
		return abstractModel::getModelInstance('$modelName',\$PK);
	}
	static public function getMultipleInstances(array \$PKs){
		return abstractModel::getMultipleModelInstances('$modelName',\$PKs);
	}
	static public function getFilteredInstances(\$filter=null){
		return abstractModel::getFilteredModelInstances('$modelName',\$filter);
	}
	static public function getFilteredInstancesByField(\$field,\$filterType,\$args=null){
		return abstractModel::getFilteredModelInstancesByField('$modelName',\$field,\$filterType,\$args);
	}
	static public function getInstanceFromDatas(\$datas,\$dontOverideIfExists=false,\$bypassFilters=false){
		return abstractModel::getModelInstanceFromDatas('$modelName',\$datas,\$dontOverideIfExists,\$bypassFilters);
	}
	static public function getAllInstances(\$withRelated=null,\$orderedBY=null){
		return abstractModel::getAllModelInstances('$modelName',\$withRelated,\$orderedBY);
	}
	static public function getPagedInstances(\$filter=null,\$pageId=1,\$pageSize=10,\$withRelated=null){
		return abstractModel::getPagedModelInstances('$modelName',\$filter,\$pageId,\$pageSize,\$withRelated);
	}
	static public function _setPagedNav(\$sliceAttrs){
		return abstractModel::_setModelPagedNav('$modelName',\$sliceAttrs);
	}

	static public function getCount(\$filter=null){
		return abstractModel::getModelCount('$modelName',\$filter);
	}

	/**
	* permit to access static dynamic methods such as getByFieldnameLessThan for php >= 5.3
	* where fieldName is a modelName::\$datas key
	* (will work like this: modelName::getByFieldnameLessThan(\$value));
	* In the time waiting for this to be handled in future version of php (at this time latest stable release is still 5.2)
	* we can use it like this: modelName::__callstatic('getByFieldnameLessThan',array(\$value))
	* (with late stating binding enable we should move this to abstractModel and replace '\$modelName' by static)
	*/
	static public function __callstatic(\$m,\$a){
		#- manage common filter getter
		if(preg_match('!getBy('.implode('|',array_keys(self::\$datasDefs)).')((?:(?:Less|Greater)(?:Equal)?Than)|Between|(?:Not)?(?:Null|Equal|Like|In))!',\$m,\$match)){
			return call_user_func('abstractModel::getFilteredModelInstancesByField','$modelName',\$match[1],\$match[2],\$a);
		}
	}
	/**
	* return a static property of the model (even protected).
	* @param string \$propName name of the static property to retrieve.
	* @return mixed
	*/
	static public function _getStatic(\$propName){
		return abstractModel::_getModelStaticProp('$modelName',\$propName);
	}
	static public function _getDbAdapter(){
		return abstractModel::getModelDbAdapter('$modelName');
	}
	static public function hasRelDefs(\$relType=null,\$returnDef=false){
		return abstractModel::modelHasRelDefs('$modelName',\$relType,\$returnDef);
	}
}";
$str2 = "<?php
/**
* autoGenerated on ".date('Y-m-d')."
* @package models
* @subpackage $prefix
* @class $modelName
*/

require dirname(__file__).'/BASE_$modelName.php';

class $modelName extends BASE_$modelName{
	// define here all your user defined stuff so you don't bother updating BASE_$modelName class
	// should also be the place to redefine anything to overwrite what has been unproperly generated in BASE_$modelName class
	/**
	* list of filters used as callback when setting datas in fields.
	* this permitt to automate the process of checking datas setted.
	* array('fieldName' => array( callable filterCallBack, array additionalParams, str errorLogMsg, mixed \$errorValue=false);
	* 	minimal callback prototype look like this:
	* 	function functionName(mixed \$value)
	* 	callback have to return the sanitized value or false if this value is not valid
	* 	logMsg can be retrieved by the metod getFiltersMsgs();
	* 	additionnalParams and errorLogMsg are optionnals and can be set to null to be ignored
	* 	(or simply ignored but only if you don't mind of E_NOTICE as i definitely won't use the @ trick)
	*   \$errorValue is totally optional and permit to specify a different error return value for filter than false
	*   (can be usefull when you use filter_var to check boolean for example)
	* )
	*/
	static protected \$filters = array();

	static protected \$modelName = '$modelName';

	/** formatString to display model as string */
	static public \$__toString = '';

	/** names of modelAddons this model can manage */
	static protected \$modelAddons = array();"
	.(empty($methods)?'':"\n\n\t###--- AUTOGENERATION PROCESS PROPOSED THOOSE ADDITIONAL METHODS ---###".implode('',$methods)).
	"
}
";
		$baseFile = $this->outputDir."BASE_$modelName.php";
		$userFile = $this->outputDir.$modelName.'.php';
		if( (! is_file($baseFile)) || $this->onExist === 'o' )
			file_put_contents($baseFile,$str);
		elseif( $this->onExist === 'a' )
			file_put_contents($baseFile,$str,FILE_APPEND);

		if(! file_exists($this->outputDir."$modelName".'.php') )
			file_put_contents($this->outputDir."$modelName".'.php',$str2);
	}

	static protected function __prefix($name,$prefix){
		return $prefix.(preg_match('![a-z]$!i',$prefix)?ucFirst($name):$name);
	}

}
