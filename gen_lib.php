<?php 


function htmldecode($html){
	$trans=array_flip(get_html_translation_table(HTML_ENTITIES));
	return strtr($html,$trans);

}

/**
* Return the first existing file from the list of filenames provided.
* @return string the name of the first existing file
*/
function firstExistingFile(){
    $filenames = func_get_args();
    //echo "<br />";print_r($filenames);
    foreach($filenames as $filename) {
        if (file_exists($filename)){
            return $filename;
            }
    }
}

 /* "fix" the IE image button issue */
 function getButton(){
    $thebutton="Cancel";
    if ($_REQUEST['okbutton']!='')  {$thebutton='OK';}
    if ($_REQUEST['okbutton_x'] !="") {$thebutton='OK';}
    return $thebutton;
 }
    

/*
* error reporting functions
*/

/**
* Error handler function
*
* @param string $errno The error number
* @param string $errstr The error message itself
* @param string $errfile The filename of the source file in which the error occurred
* @param string $errline The line number in which the error occurred
*/
function catvizErrorHandler ($errno, $errstr, $errfile, $errline) {
  switch ($errno) {
    case E_USER_ERROR:
      // Show we have class until the end: styled fatal error message!
      
      echo '<?xml version="1.0" encoding="iso-8859-1"?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
      echo '<html xmlns="http://www.w3.org/1999/xhtml">';
      echo '<head>';
      echo '<title>Error</title>';
      echo "<link rel=\"STYLESHEET\" href=\"themes/{$GLOBALS['theme']}/css/style.css\" type=\"text/css\" />";
      echo '</head>';
      echo '<body>';
  
      echo "<div class=\"error-message\"><b>FATAL</b> [$errno]\n";
      echo "<br />Fatal error in line ".$errline." of file ".$errfile;
      echo "<br />Error description: $errstr";
      echo "<br />Aborting...<br /></div>\n";
      echo "</body></html>";
      exit(1);
      break;
    case E_USER_WARNING:
      $GLOBALS['catviz_info']->error_content .= "<h1>ERROR</h1> [$errno] $errstr in line $errline of $errfile<br />\n";
      break;
    case E_USER_NOTICE:
      $GLOBALS['catviz_info']->error_content .= "<h1>WARNING</h1> [$errno] $errstr in line $errline of $errfile<br />\n";
      break;
    case E_WARNING:
      $GLOBALS['catviz_info']->error_content .= "<h1>WARNING</h1> [$errno] $errstr in line $errline of $errfile<br />\n";
      break;
    default:
      break;
  }  
}

/**
* TCatvizInfo holds information that is useful for debugging
* and error messages
*/

class TCatvizInfo {
    var $current_query;
    var $current_form;
    var $current_module;
    var $error_content;
    /**
    * Constructor for TCatvizInfo
    * Does nothing at all
    */
    function TCatvizInfo(){}
    
    /**
    * Get info on current status of execution: Modulename, formname and last SQL query
    * @return string HTML formatted message containing statusmessage
    */
    function showInfo(){
        $content  = "<b>Module:</b> $this->current_module<br />";
        $content .= "<b>Form:</b> $this->current_form<br />";
        $content .= "<b>Query:</b> $this->current_query<br />";
        return $content;
    }

    /**
    * Get last error message and store it in the CNT_error content variable
    */
    function showErrorMessage(){
        if ($this->error_content!=""){
            $info=$GLOBALS['catviz_info']->showInfo();
            $GLOBALS['content']['CNT_error']="<div class=\"error-message\"><h1>Error</h1><br />$this->error_content<br />$info</div>";
        }    
    }
}



/**
* Generate the HTML code a hidden field
*
* @param string $name The name of the field
* @param string $value The value of the field
* @return string The HTML formatted hidden input field
*/
function hiddenField($name, $value=NULL) {
    return "<input type=\"hidden\" name=\"$name\" value=\"$value\" />";
}

/**
* Run a query. Log in to the database server if needed.
* The global variable 'mysqlserver' is used to store the
* database handle.
*
* @param string $query The query to be executed
*/ 
function runQuery($query){
    global $dbhost,$dbuser,$dbpassword,$dbname;
    //print "Query: $query <br />";
    $GLOBALS['catviz_info']->current_query=$query;
    if ($GLOBALS["mysqlserver"] == NULL) {
        $GLOBALS["mysqlserver"] = mysql_connect($dbhost,$dbuser,$dbpassword);
        mysql_select_db($dbname,$GLOBALS["mysqlserver"]) ;
    }
    $result=mysql_query($query,$GLOBALS["mysqlserver"]);
    if ($result){
    	return $result;
    }else{
    	trigger_error("SQL error, query was:<br/ ><br /> $query <br />",E_USER_ERROR);		
    }
}

/**
 * get a string from the string repository.
 * @param string $key The string key value
 * @param string default_value The string's default value
 */
 
 function getString($key,$default_value){
 	if ($GLOBALS['translate_learn_mode']){
 		//echo "Learning $key $default_value <br />";
 		$query="select * from mod_string where string_key=\"$key\""; 		
 		$sql_result=runQuery($query);
        if (!mysql_fetch_array($sql_result)) {
 			$query="insert into mod_string set string_key=\"$key\",string_value=\"$default_value\"";
 			runquery($query);
        }
 		return $default_value;
 		
 	}else {
 		
 		//echo "Translating $key";
 		$query="select * from mod_string left join mod_string_xlat using(string_id) left join mod_string_language using(language_id) where string_key=\"$key\" and language_name=\"{$GLOBALS['language']}\"";
 		$sql_result=runQuery($query);
        if (($row=mysql_fetch_array($sql_result))) {
        	return $row['xlat_value'];
        }else{
        	return $default_value;
        }
        
 	}
 }

/**
* Determine if the current user has the indicated control/zone
* @param string $access_class The access class in question
*
* @param string $acces_class The access class in question
* @return boolean True if user has the indicated access class
*/

function hasAccessClass($access_class){
    return $_SESSION['module']["mod_userman"]->hasAccessClass($access_class);
}

/**
* Determine if the current user has admin rights
* @return boolean True if the user has admin rights
*/
function isAdmin(){
    return $_SESSION['module']["mod_userman"]->isAdmin();
}

/**
* Create a url for a form
*/
function formURL($module,$form,$action,$foreign_key_value='',$opt=''){
	if ($opt)
		return "index.php?{$module}_form=$form&amp;$action=$foreign_key_value&amp;opt=$opt";
	else
		return "index.php?{$module}_form=$form&amp;$action=$foreign_key_value";
}

/**
*
* A baseclass to hold fields
*
* This class is used to hold (simple) fields and can be used to
* derive other field classes.
* If no title is given it is derived from the fieldname. Underscores are replaced
* by spaces and the name is capitalized properly.
*
* @author Joost Horward
* @access public
* @package catviz
*/

class TField{
    /**
    * The name of the field. Needs to correspond to the database field if any.
    * @var string 
    */
    var $name;

    /**
    * The name of the field in the database relating to this field. Usually the same as $name
    * @var string
    * @see $name
    */
    var $dbname;

    /**
    * The description used in header or dialog to describe this field
    * @var string 
    */
    var $title;

    /**
    * The type of this field: string,integer,float, date, time, choice ...
    * @var string 
    */
    var $type;

    /**
    * The size of this field.
    * @var integer 
    */
    var $size;

    /**
    * Holds the name of the current module. 
    * @var string 
    */
    var $modulename;

    /**
    * Defaultvalue is the default value for this field, used for example when a new entry is added to the database
    * @var string 
    */
    var $defaultvalue;

    /**
    * Reference to the form that contains this field
    * @var string 
    */
    var $form;
        
    /**
    * The field attributes
    *
    * i = invisible
    * r = readonly
    * h = hidden
    * e = evaluate default value
    * m = needs multipart form
    * n = allow NULL (none) in list
    * j= indexed (for TSelectField etc.)
    * u= multiple select allowed
    * a= use htmlArea (for TTextArea fields)
    */
    
    var $attributes;
    /**
    * The formatstring
    * @var string
    */
    
    var $formatstring;
    
    /**
    * Constructor
    * Creates title from fieldname if no title is given
    * Creates fieldname from title if no fieldname is given
    *
    * @param TForm &$theform Reference to the form that holds this field instance
    * @param string $thename The name of this field
    * @param string $thetitle The title of this field, used in list headings etc.
    * @param string $thetype The type of this field, ("string", "boolean" etc.)
    * @param int $thesize The length of this field - default 25
    * @param string $thedefaultvalue - see $defaultvalue    
    * @param string $theattribs
    * @param string $thedbname The db fieldname for this field - leave blank to be equal to $thename
    *
    */
	
	/**
	* The current value of the field
	* @var string
	*/
	var $_value;
    
    function TField (&$theform,$thename,$thetitle="",$thetype,$thesize=25,$thedefaultvalue="",$theattributes="",$thedbname="") {
        // Construct title from name if no title given
        if ($thetitle=="") {
            $thetitle=strtr($thename,'_',' ');
            $thetitle=ucwords(strtolower($thetitle));
        }
        // Construct name from title if no title given
        if ($thename=="") {
            $thename=strtr($thetitle,' ','_');
            $thename=(strtolower($thename));
        }
        $this->name   = $thename;
        
        // Generate a dbname if none is given
        if ($thedbname<>"") {
            $this->dbname=$thedbname;
        } else {
            $this->dbname = $thename;
        }
        
        $this->title=$thetitle;
        $this->type=$thetype;
        $this->size=$thesize;
        
        $this->form=&$theform;
        
        $this->modulename = $this->form->module->name;        
        
        $this->defaultvalue=$thedefaultvalue;
		
		$this->_value=$thedefaultvalue;
        
        $this->attributes=$theattributes;
    }
    
    /**
    * Command dispatcher for TField
    * Make the field perform an action
    * Looks at form_new to determine usage of default values
    * @param string $field_action The name of the action to perform (edit, show, ....)
    * @param string $value the value to show/edit etc.
    * Other values may be retrieved by using the $form link to the form
    * @return string The HTML code to show for this action
    */    
    function action(){
		$field_action=func_get_arg(0);
        /**
        * Check form_new request value. if true a form is created for a new entry (rather than
        * editing an existing one) this invokes the use of default field values and an INSERT
        * rather than an update query
        */
		if (func_num_args()>1) {
			$this->_value=func_get_arg(1);
		}
		
        //$form_new=$GLOBALS['req_form_new'];
        
        // If the s bit is set field will always show rather than edit
        if ($this->hasAttribute('s') & $field_action=="edit"){
        	$field_action="show";
        }
        
        if ($field_action<>""){
            if ($this->hasAttribute('r') & $field_action=='edit') { $field_action="readonly"; }
            if ($this->hasAttribute('h') & ($field_action=='edit' | $field_action=='show')) {$field_action='hidden';}
            $thefunction="On_$field_action";
            $content=$this->$thefunction();

        }
        else
            $content=$this->On_default();
        return ($content);
    }
    
    /**
    * Show an edit box with the field value in it
    * @return string The HTML code for editing this field
    */
    function on_Edit(){
        $varname =  "tmodule_" . $this->name;
        $htmlvalue=htmlspecialchars($this->_value);
        $content="<input type=\"text\" name=\"$varname\" value=\"$htmlvalue\" size=\"$this->size\" class=\"{$this->form->style}\" />";
        return($content);
    }
    
    /**
    * Show the field value (non-editable)
    * There is a subtle difference between On_show and On_readonly
    * On_show does *not* yield a form value that gets inputted
    * @see On_readonly
    * @return string The HTML code for showing this field (but also not inputting it)
    */
    function on_Show(){
    	if ($this->formatstring) 
    		return sprintf($this->formatstring,$this->_value);
    	else
    		return ($this->_value);
    }

    /**
    * Show the field title (non-editable)
    * @return string The HTML code for the title of this field
    */
    function on_Title(){
        return $this->getTitle();        
    }

    /**
    * Creates a hidden field with the given value
    * @return string The HTML code for a hidden version of this field
    */    
    function on_Hidden(){
        $varname =  "tmodule_" . $this->name;        
        return(hiddenField($varname,$this->_value));
    }
    
    /**
    * Create a read-only representation of the field
    * There is a subtle difference between On_readonly and On_show
    * On_show does not yield a form value that gets inputted
    * On_readonly *does*. This has the effect of inputting the default value in the database.
    * @see On_show
    * @return string The HTML code for showing this field read-only
    */    
    function on_Readonly(){
        $varname =  "tmodule_" . $this->name;
        return("<input type=\"TEXT\" name=\"$varname\" value=\"$this->_value\" maxsize=\"$this->size\" readonly />");
    }
    /**
    * Delete the field. This allows the field object to perform any
    * cleanup before the database row that it's in is deleted.
    * "param $value The current value of the field
    */
    function on_Delete(){
    }
        
    /**
    * Get the title of this field
    * @return string The HTML code for the title of this field
    */
    function getTitle() {
        // If o bit is set -> display title as link for ordering the list
        if ($this->hasAttribute('o')){
            // This removes the previous &field_order=value statement, if any.
            $uri=str_replace("&field_order={$_REQUEST['field_order']}","",$_SERVER['REQUEST_URI']);
            $uri=$this->form->getURL();
            return "<a href=\"$uri&amp;field_order=$this->dbname\">$this->title</a>";
        } else {
            return "$this->title";
        }
    }
	
	
	/**
	* Set the value of this field
	*/
	function setValue($value){
		$this->_value=$value;
	}
	
	function setDefaultValue() {
		if ($this->hasAttribute('e')){
			eval($this->defaultvalue);
			$this->_value=$value;
		}
		else{
			$this->_value=$this->defaultvalue;
		}
	}
	
	/**
	* Set the value of the field to the value specified in the $_REQUEST parameters
	*/	
	function setReqValue(){
		if (!$this->hasAttribute('i')){
		    $varname="tmodule_" . $this->name;
			$this->_value=$_REQUEST[$varname];
		}		
	}
	    
    /**
    * Get the current value of the field as returned by the submitted form
    * @return string The field value
    */
    function getValue(){
        return $this->_value;
    }
    /**
    * Determine if the field is visible (e.g. if it will appear on the form at all
    * Not to be confused with hidden fields which DO appear on the form
    * @param boolean $visible True if the field needs to be on the form
    */
    function setVisible($visible){
        if ($visible){
            $this->resetAttribute('i');
        } else {
            $this->setAttribute('i');
        }
    }
    
    /**
    * Determine if the field is visible (e.g. if it will appear on the form at all
    * Not to be confused with hidden fields which DO appear on the form
    * @return boolean $visible True if the field will be on the form
    */
    function getVisible(){
        return !$this->hasAttribute('i');
    }
    function hasAttribute($theattribute){
        return (strpos($this->attributes,$theattribute)!==FALSE);
    }
    function setAttribute($theattribute){
        if (strpos($this->attributes,$theattribute)==FALSE){
            $this->attributes .= $theattribute;
        }
    }
    
    function resetAttribute($theattribute){
        $this->attributes = str_replace($theattribute,'',$this->attributes);
    }
    
}

/**
* Field for uploading  a file
*
* This class represents a field which uploads a file
* The corresponding database field contains the associated filename
* A unique filename is constructed and stored in the database.
*
* @package catviz
* @access public
* @author Joost Horward
*/
class TUploadField extends TField{
    /**
    * @var string Holds the previous value of the field. This is needed
    * to delete the previuous file when an update of the file is done.
    * The field variable content is constructed as [uniqid]_orginial_filename
    */
    var $remembervalue;
    /**
    * @var string The path to the files to be stored. By default var/mod/[modulename]/[fieldname]
    */
    var $filepath;
    
    /**
    * Constructor for TUploadField
    * 
    * @see TField
    */
    function TUploadField(&$theform,$thename,$thetitle="",$thesize=25,$thedefaultvalue="",$attribs="m") {        
        $this->TField ($theform,$thename,$thetitle,"upload",$thesize,$thedefaultvalue,$attribs);
        $this->remembervalue="";
        $modulename=$this->form->modulename;        
        $this->filepath="var/mod/$modulename/{$this->name}";
    }

    /**
    * Edit the upload field
    */
    function on_Edit(){
        $this->remembervalue=$this->_value;
        $htmlvalue=htmlspecialchars($this->_value);
        $original_filename=substr(strstr($this->_value,'_'),1);
        $content="<input name=\"tmodule_{$this->name}\" type=\"file\" value=\"$htmlvalue\" size=\"$this->size\" class=\"{$this->form->style}\" />";
        if ($original_filename) {$content = "$original_filename - upload new: $content"; }
        return $content;
    }
    
    /**
    * Delete the field, e.g. delete it's file.
    */
    function on_Delete(){
        if (($this->_value!="") & file_exists("{$this->filepath}/{$this->_value}")){
            unlink("{$this->filepath}/$this->_value");
        }
    }
    
    /**
    * Get the value to be stored in the database, [uniqid]_filename
    */    
    function setReqValue(){
        $thename=$_FILES["tmodule_{$this->name}"]['name'];
        if ($thename){
            $newfilename=uniqid("") . '_' . basename($thename);
            move_uploaded_file($_FILES["tmodule_{$this->name}"]['tmp_name'],"{$this->filepath}/$newfilename");
            // remove the old file
            if (($this->remembervalue!="") & file_exists("{$this->filepath}/{$this->remembervalue}")){
                unlink("{$this->filepath}/{$this->remembervalue}");
            }
			$this->_value=$newfilename;            
        } else {
			$this->_value=$this->remembervalue;
        }
    }
    
    /**
    * Show the field, e.g. a link to the file
    */
    function on_Show(){
        return "<a href=\"var/mod/$this->form->modulename/$this->_value\">Download file</a>";
    }
}

/**
* Image upload field
*
* @author Joost Horward
* @access public
* @package catviz
*/
class TImageUploadField extends TUploadField{
    var $default_image;
    function TImageUploadField (&$theform,$thename,$thetitle="",$thesize=25,$thedefaultvalue="",$attribs="m") {
        // default value is blank. This prevents the default to be indistinguishable from a normal value.
        $this->TUploadField ($theform,$thename,$thetitle,$thesize,"",$attribs);
        $this->default_image=$thedefaultvalue;
        $this->type="imageupload";
    }

    function on_Edit(){
        $this->remembervalue=$this->_value;
        $htmlvalue=htmlspecialchars($this->_value);
        if ($this->_value){
            $content="<img src=\"var/mod/{$this->form->modulename}/{$this->name}/$this->_value\" alt=\"$htmlvalue\" class=\"{$this->form->style}\" align=\"left\" /><br />";
        }
        return "$content<input name=\"tmodule_{$this->name}\" value=\"$htmlvalue\" type=\"file\" class=\"{$this->form->style}\" />";
            
    }
    
    function on_Show(){
        global $theme;
        $htmlvalue=htmlspecialchars($this->_value);
        if ($this->_value){
            return "<img src=\"var/mod/{$this->form->modulename}/{$this->name}/$this->_value\" alt=\"$htmlvalue\" class=\"{$this->form->style}\" />";
        }else{
            $imagefilename=firstExistingFile("themes/$theme/mod_{$this->form->modulename}/{$this->default_image}","themes/$theme/mod/{$this->form->modulename}/{$this->default_image}","mod/{$this->form->modulename}/img/{$this->default_image}");        
            if ($imagefilename){
                return "<img src=\"$imagefilename\" class=\"{$this->form->style}\" />";
            }
        }
    }
}

/**
* Keeps a value or expression in the session
* This allows default values to be inserted when records are created in the database
* and updates to be made (counters, timestamps etc)
* the values of $show_expression and $update_epression should contain a valid PHP
* epression in the form $value = ...
* Invisible by default
*/
class TEvalField extends TField{    
    /*
    * @var string The exoression to be executed at edit time (e.g. when the form is shown)
    */
    var $edit_expression;
    
    /*
    * @var string The exoression to be executed at update time (e.g. when the form is submitted)
    */    
    var $update_expression;
    
    /**
    * TEvalField constructor
    *
    * attribs is set to 'i' (invisible) by default
    */
    function TEvalField(&$theform,$thename,$thetitle="",$thesize=25,$thedefaultvalue="",$attribs="i") {        
        $this->TField ($theform,$thename,$thetitle,"eval",$thesize,$thedefaultvalue,$attribs);
    }
    
    /**
    * Do the form-edit-time evaluation
    */
    
    function on_Edit(){
        if ($this->edit_expression){
            eval ($this->edit_expression);
			$this->_value=$value;
        }
        return $this->_value;
    }
    
    /**
    * on_Show does exactly the same as on_Edit
    * @see on_Edit
    */        
    function on_Show(){
        return $this->on_Edit($this->_value);
    }
    
    /**
    * Get the value to store in the database
    * Evaluates the 'update' expression
    */
    function setReqValue(){
        if ($this->update_expression){
            eval ($this->update_expression);
			$this->_value=$value;
        }
    }
}

/**
*
* A class for stringfields
* Does little different than TField but TField and TStringField are separate for clarity's sake.
*
* @author Joost Horward
* @access public
*/
class TStringField extends TField {
    function TStringField (&$theform,$thename,$thetitle="",$thesize=25,$thedefaultvalue="",$attribs="") {        
        $this->TField ($theform,$thename,$thetitle,"string",$thesize,$thedefaultvalue,$attribs);        
    }
}

/**
* A class for LinkFields (for the form toolbar)
*
* @author Joost Horward
* @access public
*/

class TLinkField extends TField {
    /**
    * @var string Holds the link URL
    */
    var $link;
    
    /**
    * @var string Holds any extra HTML attributes that the link may have
    */
    var $extra_html_attributes;
    
    /**
    * Constructor
    */    
    function TLinkField (&$theform,$thename,$thetitle="",$thelink="") {        
        $this->TField ($theform,$thename,$thetitle,"link",25,'',"");
        $this->link=$thelink;
    }
    /**
    * Show the field - simple HTML link
    */
    function on_Show(){
        return "<a href=\"$this->link\" $this->extra_html_attributes>$this->title</a>";
    }

    /**
    * Set the link (URL) of this field
    * @param string $link The URLfor this field
    */    
    function setLink($link){
        $this->link=$link;
    }
    
    /**
    * Set the extra HTML attributes of this field
    * @param string $extra_html_attributes
    */    
    function setExtraHTMLAttributes($extra_html_attributes){
        $this->extra_html_attributes=$extra_html_attributes;
    }    
}

/**
*
* A class for LinkFields (for the form toolbar)
*
* @author Joost Horward
* @access public
*/
class TIconLinkField extends TLinkField {
    /**
    * @var string holds the name of the icon image representing this link
    */
    var $image;
    
    /**
    * TIconLinkField constructor
    * @param string $theimage The name of the icon image representing this link
    */
    function TIconLinkField (&$theform,$thename,$thetitle="",$thelink="",$theimage="") {        
        $this->TLinkField($theform,$thename,$thetitle,$thelink);
        $this->image=$theimage;
    }
    
    /**
    * Show the field a HTML link with an image. Uses title as HTML title field showing a tooltip in most browsers
    */
    function on_Show(){
        global $theme;
        $imagefilename=firstExistingFile("themes/$theme/img/$this->image","lib/img/$this->image");
        return "<a href=\"$this->link\" title=\"$this->title\" $this->extra_html_attributes><img border=\"0\" src=\"$imagefilename\" alt=\"$imagefilename\" /></a>";
    }
}

/**
*
* This creates an edit icon that links to another form.
*
* @author Joost Horward
* @access public
*/
class TOtherFormLinkField extends TIconLinkField {
    /**
    * @var string holds the name of the icon image representing this link
    */
    var $image;
    var $text;
    var $tooltip;
    
    var $callform;
    var $callaction;
    
    /**
    * TIconLinkField constructor
    * @param string $theimage The name of the icon image representing this link
    */
    function TOtherFormLinkField (&$theform,$thename,$thetitle,$theimage,$thetext,$thetooltip,$thecallform,$thecallaction) {        
        $this->TIconLinkField($theform,$thename,$thetitle,'',$theimage);
        $this->text=$thetext;
        $this->tooltip=$thetooltip;
        $this->callform=$thecallform;
        $this->callaction=$thecallaction;
    }
    
    /**
    * Show the field a HTML link with an image. Uses title as HTML title field showing a tooltip in most browsers
    */
    function on_Show(){
        global $theme;
        $imagefilename=firstExistingFile("themes/$theme/img/$this->image","lib/img/$this->image");
        $module=$this->form->module->name;
        //$link="index.php?module=$module&amp;{$module}_op=form&amp;form_name={$this->callform}&amp;form_action={$this->callaction}&amp;foreign_key_value={$this->form->foreign_key_value}";
        $link=formURL($module,$this->callform,$this->callaction,$this->form->foreign_key_value);
        if ($this->image)
        	return "<a href=\"$link\" title=\"{$this->tooltip}\" $this->extra_html_attributes><img border=\"0\" src=\"$imagefilename\" alt=\"{$this->text}\" /></a>";
        else 
        	return "<a href=\"$link\" title=\"{$this->tooltip}\" $this->extra_html_attributes>{$this->text}</a>";        	                	
    }
}




/**
* Like TStringField but shows a password input box (with ***** instead of feedback text)
* Passwords are MD5 encoded.
*/
class TPasswordField extends TField {
    function TPasswordField (&$theform,$thename,$thetitle="",$thesize=25,$thedefaultvalue="",$attribs="") {        
        $this->TField ($theform,$thename,$thetitle,"string",$thesize,$thedefaultvalue,$attribs);        
    }
    
    /**
    * Show an edit box with the field value in it
    * @return string The HTML code for editing this field
    */
    function on_Edit(){        
        $varname =  "tmodule_" . $this->name;        
        $content="<input type=\"password\" name=\"$varname\" value=\"\" size=\"$this->size\" class=\"{$this->form->style}\" />";
        return($content);
    }
    
    /**
    * Show the field value (non-editable)
    * since this is a password always shows a blank
    */
    function on_Show(){
    return ("");
    }

    function setReqValue(){
    	parent::setReqValue();
      	$this->_value=md5($this->_value);
    }

}

/**
* A Date field that has a javascript popup calendar attached
* The Javascript and CSS to be included in the template
* If the Javascript and CSS are not included no harm done but also no popup calendar :(
* The text input is fed through strtotime so alomst any English text date representation is recognised.
*/
class TDateField extends TField {    
    /**
    * Constructor of TDateField
    * For parameter descriptions see TField
    * @see TField
    */
    function TDateField(&$theform,$thename,$thetitle="",$thedefaultvalue="",$theattribs="") {
        $this->TField($theform,$thename,$thetitle,"date",8,$thedefaultvalue,$theattribs);
    }
    
    /**
    * Create the edit field with the popup calendar
    * @param $value date The date to be shown
    * @return string The HTML code for editing this field
    */    
    function on_Edit (){
        $this->_value=date("d-M-Y",strtotime($this->_value));
        $content .= "<input type=\"text\" name=\"tmodule_$this->name\" value = \"$this->_value\" id=\"$this->name\" size=\"11\" class=\"{$this->form->style}\" />&nbsp;<input type=\"reset\" value=\" ... \" class=\"{$this->form->style}\" onclick=\"return showCalendar('$this->name', 'd-M-y');\" /> ";
        $GLOBALS['content']['FLG_calendar']='1';
        return ($content);
    }
    
    function setReqValue(){
        $varname="tmodule_" . $this->name;
        $thedate=$_REQUEST[$varname];
        if ($thedate) {
        	$this->_value=date("Y-m-d",strtotime($thedate));
        }
    }
    
    function on_Show(){
        return (date("d M Y",strtotime($this->_value)));
    }
}

/**
* A checkbox field that represents a boolen
*
* Shows an image for the show() method
*/

class TBooleanField extends TField {
  
    /**
    * The constructor for TBooleanField
    * See TField for a description of the parameters
    * Just calls parent.
    * @see TField
    */
    function TBooleanField(&$theform,$thename,$thetitle="",$thedefaultvalue="",$theattribs="") {
        $this->TField($theform,$thename,$thetitle,"boolean",1,$thedefaultvalue,$theattribs);
    }
    
    /**
    * Generate the checkbox edit field
    * @param boolean $value The value to be shown
    * @return string The HTML code for editing this field
    */    
    function on_Edit (){
        $varname =  "tmodule_" . $this->name;
        if ($this->_value) {$checked="checked=\"checked\"";}
        $content=  "<input type=\"checkbox\" name=\"$varname\" value=\"1\" $checked />";
        return ($content);
    }

    /**
    * Generate the checkbox show field
    * It simply consists of a ticked or non-ticked png of a checkbox
    * @param boolean $value The value to be shown
    * @return string The HTML code for showing this field
    */
    function on_Show(){
    if ($this->_value<>0) {
        $content = "<img border=\"0\" src=\"lib/img/checkbox-checked.png\" alt=\"Checked\" />";
    }
    else
        $content = "<img border=\"0\" src=\"lib/img/checkbox.png\" alt=\"Unchecked\" />";
    return $content;
    }
    
    /**
    * Get the field value in the form to be stored in the database, e.g. 0 or 1
    */
    function setReqValue(){
        $varname="tmodule_" . $this->name;
        $this->_value=$_REQUEST[$varname];
        if ($this->_value=="") $this->_value = '0';
    }
}

/**
* A Radio button field
*
* The radio button field represents a varchar field in the database
* Each option has a name which is stored in the varchar field when selected.
*
*
*/

class TRadioField extends TField {
    /**
    * Holds the options to be shown in the radio field
    * @var array 
    */
    var $options;
    
    /**
    * Constructor for TRadioField
    * Initializes the options array and calls TField
    * @see TFields
    * @param array $theoptions An array with the options to be shown
    */
    function TRadioField(&$theform,$thename,$thetitle="",$theoptions,$thedefaultvalue="",$theattribs="") {
        $this->options=$theoptions;
        $this->TField($theform,$thename,$thetitle,"radio",25,$thedefaultvalue,$theattribs);
    }
    
    /**
    * Generate the radiobutton edit field
    * @param $value string The value to be shown as default
    * @return string The HTML code for editing this field
    */    
    function on_Edit (){
        $varname =  "tmodule_" . $this->name;
        foreach ($this->options as $option) {            
            $checked = $this->_value==$option ? "checked=\"checked\"" : "";
            $content .= "<input type=\"radio\" name=\"$varname\" value=\"$option\" $checked />$option";
            $content .="<br />";
        }
    return ($content);
    }
}

/*
* A Drop-down Field class
*
* This class represents a varchar field with a drop-down control
* 
*/

class TDropDownField extends TField {
    
    /**
    * Holds the options to be shown in the radio field
    * @var array
    */
    var $options;

    /**
    * Constructor for TDropDownField
    * Initializes the options array and calls TField
    * @see TFields
    * @param array $theoptions An array with the options to be shown
    */
    function TDropDownField(&$theform,$thename,$thetitle="",$theoptions,$thedefaultvalue="",$theattribs="") {
        $this->options=$theoptions;
        $this->TField($theform,$thename,$thetitle,"dropdown",25,$thedefaultvalue,$theattribs);
    }

    /**
    * Generate the drop-down edit field
    * @return string The HTML code for editing this field
    */    
    function on_Edit (){
        $varname =  "tmodule_" . $this->name;
        $content="<select name = \"$varname\" class=\"{$this->form->style}\">";
        foreach ($this->options as $option) {
            $selected = $this->_value==$option ? "selected=\"selected\"" : "";
            $content .="<option class=\"{$this->form->style}\" value=\"$option\" $selected>$option</option>"; 
        }
        $content .= "</select>\n";        
        return ($content);
    }
}

/*
* An improved Drop-down Field class
* Will eventually replace TDropDownField
*
* This class represents a varchar field with a drop-down control
* 
*/

class TSelectField extends TField {
    
    /**
    * Holds the options to be shown in the radio field
    * @var array
    */
    var $options;

    /**
    * Constructor for TDropDownField
    * Initializes the options array and calls TField
    * @see TFields
    * @param array $theoptions An array with the options to be shown
    */
    function TSelectField(&$theform,$thename,$thetitle="",$thesize,$thedefaultvalue="",$theattribs="") {
        $this->options=array();
        $this->TField($theform,$thename,$thetitle,"dropdown",$thesize,$thedefaultvalue,$theattribs);
    }
    
    function addOptions(){
		$options = func_get_args();
        foreach($options as $option) {
            list ($index,$name)=explode(":",$option);
            $this->options[$index]=$name;
        }    	
    }

       
    /**
    * Generate the Select edit field
    * @return string The HTML code for editing this field
    */    
    function on_Edit (){
        $varname =  "tmodule_" . $this->name;
        if ($this->hasAttribute('u')) {
        	$multiple=' multiple="multiple" ';
        	$varname = $varname . '[]';        	
        	$listsize='size ="' . min(count($this->options),$this->size) . '"'; 
        };

        $content="<select $listsize name=\"$varname\" class=\"{$this->form->style}\"$multiple>";
        

       		$values=explode(',',$this->_value);

   			foreach ($this->options as $index => $option) {
	            $selected = in_array($option,$values) ? "selected=\"selected\"" : "";
	            if ($this->hasAttribute('j')){
					$selected = in_array($index,$values) ? "selected=\"selected\"" : "";
	            	$content .="<option class=\"{$this->form->style}\" value=\"$index\" $selected>$option</option>";
	            } else {
					$selected = in_array($option,$values) ? "selected=\"selected\"" : "";
	            	$content .="<option class=\"{$this->form->style}\" value=\"$option\" $selected>$option</option>";
	            } 
        	}
        	$content .= "</select>\n";
       	   		
                
        return ($content);
        
    }
    /**
    * Get the current value of the field as returned by the submitted form
    * @return string The field value
    */
    function setReqValue(){
        $varname="tmodule_" . $this->name; 
        $this->_value=$_REQUEST[$varname];
        if (is_array($this->_value)){
			$this->_value=implode(',',$this->_value);
		}
    }
}

class TSQLSelectField extends TSelectField {
	/**
	* The SQL statement that creates the content of this select field
	* 2 aliases need to be generated by the SQL:
	* select_index the index generated by the selectfield (e.g. the value to be stored in the database)
	* select_text the text shown in for the corresponding option	
	*/
    function TSQLSelectField(&$theform,$thename,$thetitle='',$thesize,$thesql,$thedefaultvalue='',$theattribs='') {
        $this->options=array();
        $this->TSelectField($theform,$thename,$thetitle,$thesize,$thedefaultvalue,$theattribs);
        $this->sql=$thesql;
    }
    
    function on_Edit(){
    	$sql_result = runquery($this->sql);
        $this->options=array();
        while ($row = mysql_fetch_array($sql_result)) {
            $this->options[$row['select_index']]=$row['select_text'];
        }
        $content = parent::On_edit($this->_value);        
        return ($content);
    }

}




/*
* A SQL Drop-down Field class
*
* This class represents a varchar field with a drop-down control
* The query should result a value 'tag'
*/

class TSqlDropDownField extends TDropDownField {
    
    /**
    * The SQL query that yields the options to be shown
    * The query should result a value 'tag'
    * @var string
    */
    var $sql;
    
    /**
    * Constructor for TSqlDropDownField
    * Initializes the sql string and calls TField
    * @see TFields
    * @param array $thesql The query that yields the options to be shown
    * The query should result a value 'tag'
    */
    function TSqlDropDownField(&$theform,$thename,$thetitle="",$thesql,$thedefaultvalue="",$theattribs="") {
        $this->TField($theform,$thename,$thetitle,"sqldropdown",25,$thedefaultvalue,$theattribs);
        $this->sql=$thesql;
    }
    
    /**
    * Generate the drop-down SQL edit field
    * @return string The HTML code for editing this field
    */    
    function on_Edit (){        
        $sql_result = runquery($this->sql);
        $this->options="";
        while ($row = mysql_fetch_array($sql_result)) {
            $this->options[]=$row["tag"];
        }
        $content = parent::On_edit($this->_value);        
        return ($content);
    }
}

/*
* A Keyfield Chooser
*
* This class selects a foreign key
* It takes the options for the keyindex and keyname from the foreign table
* an 'n' attribute allows a null entry, shown as (none) in the selection.
*/

class TKeySelectorField extends TField {
    /**
    * The table which holds the keys and names
    */    
    var $table;
    
    /**
    * The index field name
    */
    var $keyindex;
    /**
    * The name of the field in the table that holds the descriptive name of the key
    */
    var $keyname;

    /**
    * Constructor for KeySelectorField

    * @see TField
    * @param array $thesql The query that yields the options to be shown
    * The query should result a value 'tag'
    */
    function TKeySelectorField(&$theform,$thename,$thetitle="",$thedefaultvalue="",$theattribs="",$thekeyindex,$thekeyname,$thetable) {
        $this->TField($theform,$thename,$thetitle,"keyselector",25,$thedefaultvalue,$theattribs);
        $this->keyindex=$thekeyindex;
        $this->keyname=$thekeyname;
        $this->table=$thetable;
    }
    
    /**
    * Generate the drop-down field
    * @return string The HTML code for editing this field
    */
    function on_Edit (){
        // Add (none) entry if NULL is allowed
        if ($this->hasAttribute('n')){
            $options[0]="(none)";
        }
        $sql="select $this->keyindex as keyindex,$this->keyname as keyname from $this->table";
        $varname =  "tmodule_" . $this->name;
        $sql_result = runquery($sql);
        while ($row = mysql_fetch_array($sql_result)) {
            $thisindex=$row["keyindex"];
            $thisname=$row["keyname"];
            $options[$thisindex]=$thisname;
        }

        $content="<select name = \"$varname\">";
        foreach ($options as $optionindex =>$optionname) {
            $selected = $this->_value==$optionindex ? "selected=\"selected\"" : "";
            $content .="<option value=\"$optionindex\" $selected>$optionname</option>"; 
        }
        $content .= "</select>\n";                        
        return ($content);
    }

    /**
    * Generate the drop-down field
    * @return string The HTML code for editing this field
    */
    function on_Show (){
        $sql="select $this->keyname as keyname from $this->table where $this->keyindex={$this->_value}";        
        $sql_result = runquery($sql);
        if ($row = mysql_fetch_array($sql_result)) {
            $content=$row['keyname'];
        }                
        return ($content);
    }
}


/*
* A Webpage Chooser
*
* Field that selects a link to a webpage from mod_webpages
* Looks like a drop-down edit control in edit mode
* Looks like a link in show mode
*/

class TWebpageSelectorField extends TKeySelectorField {
    /**
    * Constructor for WebpageSelectorField

    * @see TKeySelectorField
    */
    function TWebpageSelectorField(&$theform,$thename,$thetitle="",$thedefaultvalue="",$theattribs="") {    	
    	$this->TKeySelectorField(&$theform,$thename,$thetitle,$thedefaultvalue,$theattribs,"webpage_id","title","mod_webpages");
    }
    
    /**
    * Generate the drop-down field
    * @param string $value The value to be shown as default
    * @return string The HTML code for editing this field
    * This is 90% a copy of the TKeySelectorfield method. Maybe the class needs redesigning.
    */
    function on_Edit (){
         // Add (none) entry if NULL is allowed
        if ($this->hasAttribute('n')){
            $options[0]="(none)";
        }
        
        $allowed_document_classes=$GLOBALS['module']['mod_userman']->getDocumentWriteClasses();
        if($GLOBALS['module']['mod_userman']->isAdmin()){
            $sql="select $this->keyindex as keyindex,$this->keyname as keyname from $this->table order by keyname";
        } else {
            $sql="select $this->keyindex as keyindex,$this->keyname as keyname from $this->table where find_in_set(document_class_id,\"$allowed_document_classes\")>0 order by keyname";
        }
        $varname =  "tmodule_" . $this->name;
        $sql_result = runquery($sql);
        while ($row = mysql_fetch_array($sql_result)) {
            $thisindex=$row["keyindex"];
            $thisname=$row["keyname"];            
            $options[$thisindex]=$thisname;
        }

        $content="<select name = $varname>";
        foreach ($options as $optionindex =>$optionname) {
            $selected = $this->_value==$optionindex ? "selected=\"selected\"" : "";
            $content .="<option value=\"$optionindex\" $selected>$optionname</option>"; 
        }
        $content .= "</select>\n";        
                
        return ($content);
    }
    
    
    /**
    * Generate the webpage link
    * @return string The HTML code for editing this field
    */
    function on_Show (){
        $sql="select $this->keyname as keyname from $this->table where $this->keyindex={$this->_value}";        
        $sql_result = runquery($sql);
        if ($row = mysql_fetch_array($sql_result)) {
            $pagename=$row['keyname'];
            $url=formURL('webpages','webpage_multi_edit','webpage',$this->_value);
            $content="<a href=\"$url\">$pagename</a>";
            //$content="<a href=\"index.php?module=webpages&amp;webpages_op=form&amp;form_name=webpage_multi_edit&amp;form_action=webpage&amp;foreign_key_value={$this->_value}\">$pagename</a>";
        }                
        return ($content);
    }
}

/**
* A class representing a varchar field in a TextArea control
* Adds the javascript code to insert bold, italic, break, etc.
*
*/

class TTextAreaField extends TField {
    /**
    * The height of the textareafield
    * @var integer 
    */
    var $rows;
    
    /**
    * The width of the textareafield
    * @var integer 
    */
    var $cols;
    
    /**
    * Constructor for TTextAreaField
    * Stores the size of the field and calls TField
    * @see TFields
    * @param integer $therows The height of the textareafield
    * @param integer $thecols The width of the textareafield
    * The query should result a value 'tag'
    */    
    function TTextAreaField(&$theform,$thename,$thetitle="",$therows,$thecols,$thedefaultvalue="",$theattribs="") {
        $this->rows=$therows;
        $this->cols=$thecols;
        $this->TField($theform,$thename,$thetitle,"textarea","",$thedefaultvalue,$theattribs);
    }
    
    /**
    * Generate the textarea edit field
    * @return string The HTML code for editing this field
    */    
    function on_Edit (){
        $varname =  "tmodule_" . $this->name;
        // removed wrap="virtual" (non-XHTML)
        $content .= "<textarea name=\"$varname\" id=\"$varname\" rows=\"$this->rows\" cols=\"$this->cols\" class=\"{$this->form->style}\">".htmlspecialchars($this->_value)."</textarea>";
        // Add code for htmlArea only if the a attribute is set.
        if ($this->hasAttribute('a') &( $GLOBALS["registry"]->getValue('','site_info','use_htmlarea')<>0) ){
        	$content .= "<script type=\"text/javascript\" defer=\"1\"> HTMLArea.replace(\"$varname\"); </script>";
        	$GLOBALS['content']['FLG_htmlarea']='1';
        }
        return ($content);
    }
}

/**
* Icons class that just shows some icons that connect to a form
*
* This is useful only in a TList where you can click on one of the
* icons and it will launch the appropriate edit form
*
*/

class TIconsField extends TField {
    /**
    * A string representing the icons to be shown
    *
    * e shows an edit icon
    * d shows a delete icon    
    * @var string 
    */
    var $icons;

    /**
    * The name of the form to be called when one of the icons is clicked
    * @var string 
    */
    var $callform;

    /**
    * Constructor for TIconsField
    * Initializes $icons and $callform array and calls TField
    * @see TFields
    * @param array $theoptions An array with the options to be shown
    */
    function TIconsField(&$theform,$thename,$thetitle,$theicons,$thecallform,$thedbname="id") {
        $this->icons=$theicons;
        $this->callform=$thecallform;
        $this->TField($theform,$thename,$thetitle,"icons");
        $this->TField($theform,$thename,$thetitle,"icons",25,"","",$thedbname);
    }
    
    /**
    * Generate the code for the action icons
    *
    * Calls $thecallform with $foreign_key_value with the keyfield value of the line that was clicked on
    * and action edit or delete as appropriate
    * @param $value integer The ID of the database row in question
    * @return string The HTML code for showing this field--
    */
    function on_Show(){
        global $theme;
        $callform=$this->callform;
        $name=$this->modulename;
        $op = $this->modulename . "_op";
        $id = $this->modulename . "_id";
        for ($i=0;$i<strlen($this->icons);$i++) {
            switch ($this->icons[$i]) {
            case 'e':
                $imagefile=firstExistingFile("themes/$theme/img/edit.png","lib/img/edit.png");
                $url=formURL($name,$callform,'edit',$this->_value);
                $content .= "<a href=\"$url\" title=\"Edit entry\"><img border=\"0\" src=\"$imagefile\" alt=\"Edit\" /></a>";
                //$content .= "<a href=\"index.php?module=$name&amp;$op=form&amp;form_name=$callform&amp;form_action=edit&amp;foreign_key_value=$this->_value\" title=\"Edit entry\"><img border=\"0\" src=\"$imagefile\" alt=\"Edit\" /></a>";
            break;
            case 'd':
                $imagefile=firstExistingFile("themes/$theme/img/editdelete.png","lib/img/editdelete.png");
                $url=formURL($name,$callform,'delete',$this->_value);
                $content .= "<a href=\"$url\" title=\"Delete entry\" onClick=\"return confirm('Do you really want to delete entry {$this->_value}?')\"><img border=\"0\" src=\"$imagefile\" alt=\"Delete\" /></a>";
            break;
            }
        }
        return ("<nobr>$content</nobr>");
    }
    
    /**
    * Action for editing the icons field
    *
    * Just calls On_show
    * @see On_show
    */
    function on_Edit(){
        return $this->On_show($this->_value);
    }

}

/**
* Show a progress bar representing progress of whatever sort
* Should be linked to a field or query result that varies from 0 to 100
* Requires progressbar.php and progressbar.png to be present in the lib/img directory
*/
class TProgressBarField extends TField {

    /**
    * Constructor for TProgressBarField
    *
    * Just calls TField
    * @see TField
    */    
    function TProgressBarField(&$theform,$thefield,$thetitle) {    
        $this->TField($theform,$thefield,$thetitle,"progressbar");
    }
    /**
    * Generate the HTML for the progress bar
    * @param integer $value A value from 0 to 100 representing the progress to be shown
    */
    function on_Show(){
        $content = "<img src=\"lib/img/progressbar.php?progress={$this->_value}\" title='$this->_value' alt=\"Progress: {$this->_value}\" />";
        return ($content);
    }
    
    /**
    * Action for editing the progess bar field
    *
    * Just calls On_show
    * @see On_show
    */    
    function on_Edit(){
        return $this->On_show($this->_value);
    }
}


/**
* StatusIcons class that shows an icon representing a status of some sort.
*
* The value of the chosen field is translated into the icon filename as
* [Prefix]fieldvalue[.png]
* Currently used to show a status with happy face, sad face etc.
*
*/

class TStatusIconField extends TField {
    /**
    * The prefix of the image name
    * @var string 
    */
    var $prefix;
    
    /**
    * Constructor for TStatusIconField
    *
    * Initialize $prefix and call TField
    * @see TField
    */    
    function TStatusIconField(&$theform,$thefield,$thetitle,$theprefix) {
        $this->prefix=$theprefix;
        $this->TField($theform,$thefield,$thetitle,"icon");        
    }
    
    /**
    * Generate the HTML for the status icon field
    *
    * @param integer $value The status to be displayed
    */
    function on_Show(){    
        $name=$this->modulename;        
        $content = "<img src=\"mod/$name/img/$this->prefix{$this->_value}.png\" title='$this->_value' alt=\"{$this->_value}\" />";
        return ($content);
    }
    
    /**
    * Action for editing the status icon field
    *
    * Just calls On_show
    * @see On_show
    */      
    function on_Edit(){
        return $this->On_show($this->_value);
  }    
}

/**
* Detail button class - jump to detail list & transport foreign key value.
*
* This is useful only in a TList where you can click on one of the
* icons and it will launch the appropriate edit form
*
*/

class TDetailButtonField extends TField {
    /**
    * The name of the form to be called when the field is clicked on
    * @var string
    */
    var $callform;

    /**
    * Determines which column in the table holds the name of the module to be called
    */
    var $modulename_column;
    
    /**
    * Determine if the foreign_key_value will be populated in the target URL
    */
    var $useforeignkey;
    
    /**
    * Determine which column to show
    */
    var $showcolumn;
    
    /**
    * Show this as a tooltip
    */
    var $tooltip;
    

    /**
    * Constructor for TDetailButtonField
    *
    * Initializes $callform array and calls TField
    * @see TFields
    * @param array $thecallform The form to be called
    */
    function TDetailButtonField(&$theform,$thename,$thetitle,$thecallform,$thedbname="id",$themodulename_column="",$useforeignkey=TRUE,$theshowcolumn="",$thetooltip="") {
        $this->callform=$thecallform;
        $this->TField($theform,$thename,$thetitle,"button",25,"",'',$thedbname);
  
        $this->modulename_column=$themodulename_column;
        $this->useforeignkey=$useforeignkey;
        $this->showcolumn=$theshowcolumn;
        if ($thetooltip)
        	$this->tooltip=$thetooltip;
        else
        	$this->tooltip='Show details';	
    }
    
    /**
    * Generate the HTML code for the detail button
    *
    * @param integer $value The keyfield ID 
    */
    function on_Show(){
        $callform=$this->callform;
        if ($this->showcolumn){
        	$title=$this->form->row[$this->showcolumn];
        } else {
        	$title=$this->title;
        }
        if ($this->modulename_column!=""){
            $name=$this->form->row[$this->modulename_column];
        } else {
            $name=$this->modulename;
        }
        
        $op = $name . "_op";
        //if ($this->useforeignkey){
            //$content = "<a href=\"index.php?module=$name&amp;$op=form&amp;form_name=$callform&amp;form_action=edit&amp;foreign_key_value=$this->_value\" title=\"{$this->tooltip}\">[$title]</a>";
            $url=formURL($name,$callform,'edit',$this->_value);
            $content = "<a href=\"$url\" title=\"{$this->tooltip}\">[$title]</a>";
          /*  } else {
            
            $content = "<a href=\"index.php?module=$name&amp;$op=form&amp;form_name=$callform&amp;form_action=edit\" title=\"{$this->tooltip}\">[$title]</a>";
        }*/
        return ($content);
    }
    
    function on_Edit(){
        return $this->on_Show();
    }

}

/**
* A Stack class used to hold the forms stack
* The forms stack allows TModule to determine where to jump back to after a 'commit' or 'back'
*/

Class TStack {
    /**
    * Points at the next available position of the stack
    *
    * The stack grows upwards, i.e. when a value is addes $stackpointer increases
    * @var integer     
    */
    var $stackpointer;
    
    /**
    * The stack itself
    * @var array  
    */
    var $stack;
    
    /**
    * Constructor for TStack
    *
    * Initialize both the stack and the stack pointer
    */    
    function TStack(){
        $this->stackpointer=-1;
        $this->stack = array();
    }    

    /**
    * Push a value on the stack
    * @param string $formname The name of the form to push on the stack
    */
    function push($item){
    	//echo "<br />Pushing...";
    	//print_r($item);
        if (($this->stackpointer==-1) or ($this->stack[$this->stackpointer]<>$item)){
            array_push($this->stack,$item);
            $this->stackpointer++;    
        }
    }
    
    /**
    * Pop a value from the stack
    * @return string The name of the form
    */
    function pop(){
        if ($this->stackpointer >=0) {
            $this->stackpointer--;
            $a=array_pop($this->stack);
        	//echo "<br />Popping...";
        	//print_r($a);    
            return $a;    
        }
        else        
            return array('','','');
    }
    
    /**
    * Reset the stack by resetting the stackpointer
    */
    function reset(){
        $this->stackpointer=-1;
        $this->stack=array();
    }    
}

/**
* TToolbar
*
* @author Joost Horward
* @access public
*/

class TToolbar extends TField{
    /**
    * An array of field objects in this form
    * @var array 
    */
    var $fields;
    
    /**
    * Holds the content of the toolbar
    * @var string 
    */
    var $content;

    /**
    * A pointer to the form that contains this toolbar
    * @var string 
    */
    var $form;
    
    /**
    * The CSS class to be used
    */
    var $style;
    
    /**
    * Constructor of TToolbar
    *
    * Store the variables from the parameters and
    * initialize the fields array of field objects
    *
    *
    * @author Joost Horward
    * @access public
    * @param string $themodule Ref to the parent module
    * @param string $form_name Name of this form
    * @param string $thetable
    */
    function TToolbar(&$theform){
        $this->TField(&$theform,'toolbar','','toolbar');
        $this->fields=array();
    }
        
    /**
    * Add a field to the form
    * @param TField $afield The field to be added
    */
    function addField($afield){
        $this->fields[$afield->name]= $afield;
    }
    
    /**
    * Add several fields to the toolbar
    *
    * This replaces the construct that passed an array of fieldnames to the form's constructor
    * This function has a variable number of parameters
    */
    function addFields(){
        $fields = func_get_args();
        foreach($fields as $field_description) {
            list ($name,$type,$title,$link,$image)=explode(":",$field_description);
            switch ($type) {
                case "link" :
                    $field=&new TLinkField($this,$name,$title,$link);
                    break;
                case "icon" :
                    $field=&new TIconLinkField($this,$name,$title,$link,$image);
                    break;
                default:
                    $field=&new TLinkField($this,$name,$title,$link);
                    break;
            }
            $this->addField($field);            
        };    
    }
    /**
    * Show the toolbar: show each field with a space between them
    */
    function on_Show(){
        foreach ($this->fields as $fieldname => $field) {
            if ($field->getVisible()) {
                $content .= $this->fields[$fieldname]->On_show("");
                $content .="&nbsp;";
            }
        }
        return ($content);
    }
}

/**
* The Mother Of All Forms
*
* This class is the base class for all forms
*
* This class can be used directly to create simple forms, or to derive other form
* types from. Standard behaviour is supplied for submit and delete actions.
*
* @author Joost Horward
* @access public
*/

class TForm{
    /**
    * An array of field objects in this form
    * @var array 
    */
    var $fields;
    
    /**
    * Holds the title of this form
    * @var string 
    */
    var $title;
        
    /**
    * Holds the content of this form. This is copied into the tmodule's main_content later
    * @var string 
    */
    var $content;

    /**
    * The name of this form
    * @var string 
    */
    var $name;

    /**
    * A pointer to the module that contains this form.
    * @var string 
    */
    var $module;

    /**
    * The name of the module that contains this form.
    * @var string 
    */
    var $modulename;

    /**
    * output before the form
    * @var string 
    */
    var $prologue;

    /**
    * Output after the form
    * @var string 
    */

    var $epilogue;
    /**
    * @var string Name of the content variable for this form
    */
    
    var $contentvar;
    /**
    * @var string error message when re-trying the form
    */

    var $error;
    /**
    * @var string attributes
    * r = rootform
    * a = include 'Add New'
    * s = show always ('static')
    * w = show no warning when no access (default: show warning) 
    * e = have edit icon in toolbar 
    * n = always new (always load fields with default values regardless of
    * form_new)
    * v = volatile; form will be destroyed directly after use (does not get
    * stored in session)
    * p = persistent, form will remain in session for the life of the session
    * (never gets cleaned up)
    * c= do not perform value check
    */

    var $attributes;
    
    /**
    * @var string Access Class List
    */
    var $acl;
    
    /**
    * @var string The CSS class to be used
    */
    var $style;
 
    /**
    * @var string The Navigation Toolbar
    */
    var $navbar;

    /**
    * @var string The Action Toolbar
    */
    var $actionbar;

    /**
    * @var string The Action Toolbar Content - behaves similar to $content but holds just the action bar
    */
    var $actionbar_content;
    
    /**
    * @var string Callback functions before action
    */
    var $before_action_func;
    /**
    * @var string Callback function after action
    */
    var $after_action_func;
    
    /**
    * @var string contains an array with the module/form/action to be called after a certain button is pressed    
    * jumplist['OK'] determines which form to show after the OK button is pressed
    */
    var $jumplist;
    
    /**
     * @var string contains the name of the 'parent' form, this is where the
     * back button leads. It is *not* the parent class of the form
     */
     var $_parent_form_name;
    
    /**
    * @var string action contains the last (current) action performed by this form
    */
    var $action;
    
    /**
    * @var array persistentproperties contains list of properties that will be
    * session-persistent
    */
    var $persistentproperties=array();
    
    /**
     * @var string checkvalue contains value to test for session-consistency
     * 
     */
    var $checkvalue;
  
    /**
    * Constructor of TForm
    *
    * Store the variables from the parameters and
    * initialize the fields array of field objects
    *
    *
    * @author Joost Horward
    * @access public
    * @param string $themodule Ref to the parent module
    * @param string $form_name Name of this form
    * @param string $thetable
    */
    function TForm(&$themodule,$thename,$thetitle,$theattributes="",$the_parent_form_name=""){
        $this->module=&$themodule;
        $this->modulename=$this->module->name;        
        $this->contentvar = "CNT_main";
        $this->title=$thetitle;
        $this->name=$thename;
        //echo "<br />Initializing form $this->name";
        $this->attributes=$theattributes;
        $this->_parent_form_name=$the_parent_form_name;
        $this->style="tform-layout";
        $this->acl["world"]="";        
        
        $this->navbar=&new TToolbar($this);
        $this->navbar->addFields("back:icon:Back:/:back.png");
        
        $this->actionbar=&new TToolbar($this);
        if (strpos($theattributes,'a')!==FALSE) {$this->actionbar->addFields("addnew:icon:New:/:filenew.png");};
        
        $this->actionbar->addFields("edit:icon:Edit:/:edit.png");
        $this->actionbar->addFields("delete:icon:Delete:/:editdelete.png");
        
        $this->setTemplate();
        $this->persistentproperties[]='checkvalue';        
    }
    
    function setTemplate(){
    	$themename=$GLOBALS['theme'];
        $classname=get_class($this);
        $filename=firstExistingFile("themes/$themename/mod_{$this->modulename}/{$this->name}_box.html","themes/$themename/mod/{$this->modulename}/templates/boxes/{$this->name}.html","themes/$themename/templates/$classname.html");
        if ($filename) {
            $this->boxtemplate =& new TTemplate($this,$filename);
        }else{
        	if ($this->hasAttribute('s')) {
              $this->boxtemplate =& $GLOBALS['boxtemplate'];
        	}else{
        	  $this->boxtemplate =& $GLOBALS['mainboxtemplate'];
        	}
        }    	
    }
    
    
    // TODO ook prologue en epilogue vertalen.
    function translate(){
    	if($GLOBALS['translate_learn_mode']){
    		$query="select * from mod_string where string_key=\"{$this->module->name}:{$this->name}.title\"";
    		$sql_result=runQuery($query);
            if (!mysql_fetch_array($sql_result)) {
            	//echo "Inserting {$this->module->name}:{$this->name}";
            	$query="insert into mod_string set string_key=\"{$this->module->name}:{$this->name}.title\", string_value=\"{$this->title}\"";
            	runQuery($query);
            	//echo "<br>$query</br>";
            	if ($this->fields){
            		//echo " - fields: ";
            		foreach ($this->fields as $fieldname=>$field){
            			$query="insert into mod_string set string_key=\"{$this->module->name}:{$this->name}:{$field->name}.title\", string_value=\"{$field->title}\"";
            			runQuery($query);
            			//echo "<br>$query</br>";
            			//echo "$fieldname -";
            		}
            	}
            	//echo "<br />";
            }

    	}else {
    		// Translate the form title    		
    		/*$query="select * from mod_string_xlat left join mod_string using (string_id) left join mod_string_language on(mod_string_language.language_id=mod_string_xlat.language_id) where string_key=\"{$this->module->name}:{$this->name}.title\" and language_name=\"{$GLOBALS['language']}\"";
    		$sql_result=runQuery($query);
            if ($row=mysql_fetch_array($sql_result)) {
            	$this->title=$row['xlat_value'];
            }
            // Translate the form fields
    		$query="select * from mod_string_xlat left join mod_string using (string_id) left join mod_string_language on(mod_string_language.language_id=mod_string_xlat.language_id) where string_key like \"{$this->module->name}:{$this->name}:%.title\" and language_name=\"{$GLOBALS['language']}\"";
    		$sql_result=runQuery($query);
            while ($row=mysql_fetch_array($sql_result)) {
            	$keystrings=explode(':',$row['string_key']);
            	list($fieldname,$title)=explode('.',$keystrings[2]);
            	if($fieldname) {
            		//echo "Scan veld: {$this->module->name}:{$this->name}:%s ... <br />";
            		//echo $row['string_key'];
            		
            		$this->fields[$fieldname]->title=$row['xlat_value'];
            		//echo "setting $fieldname to {$row['xlat_value']} <br />";
            	}
            }*/
            
            $query="select * from mod_string_xlat left join mod_string using (string_id) left join mod_string_language on(mod_string_language.language_id=mod_string_xlat.language_id) where string_key like \"{$this->module->name}:{$this->name}%\" and language_name=\"{$GLOBALS['language']}\"";
            $sql_result=runQuery($query);
            while ($row=mysql_fetch_array($sql_result)) {
            	$parts=explode(':',$row['string_key']);
            	switch (count($parts)){
            		case 2:
            			list($name,$property)=explode('.',$parts[1]);
            			$this->$property=$row['xlat_value'];
            		break;
            		case 3:
            			list($fieldname,$property)=explode('.',$parts[2]);
            			if ($this->fields[$fieldname]){
            				$this->fields[$fieldname]->$property=$row['xlat_value'];
            			}
            		break;
            	}
            }
            
            
    	}
    }
    
    function loadPersistentProperties(){
    	foreach ($this->persistentproperties as $property){
    		$p=$this->module->persistentformproperties[$this->name][$property];
    		if ($p) {
    			$this->$property=$p;
    		}
    	}
    }
    // Store persistent properties in the module.    
    function savePersistentProperties(){
    	foreach ($this->persistentproperties as $property){
    		$this->module->persistentformproperties[$this->name][$property]=$this->$property;
    		    		
    	}
    
    }
    // Returns array of properties that need to be session-persistent
    // Since we selectively take care of this ourselves the array is empty
    function __sleep(){        
    	return(array());    	
    }
    function set($property,$value){
    	$this->$property=$value;
    }
    
    /*
     * This function decodes a form ID into an array with three elements:module,form and action
     * A Form ID can have three forms:
     * - a plain string representing the form name
     * - a colon separated string with either module:form or module:form:action
     * - an array with two or three elements, module,form or module,form,action 
     */
    function decodeFormID($aformid) {
    	if(!is_array($aformid)){
    		$formid=explode(':',$aformid);	
    	}else{
    		$formid=$aformid;
    	}
    	if (count($formid)==1) {
    		array_unshift($formid,$this->modulename);    		
    	}
    	if (count($formid)==2) {
    		array_push($formid,'show');    		
    	}
    	return $formid;
    }    	

    /**
    * setACL
    */
    function setACL($world,$module_user,$module_admin){
        $this->acl["world"]=$world;
        $this->acl["{$this->module->name}_user"]=$module_user;
        $this->acl["{$this->module->name}_admin"]=$module_admin;
    }
    
    /**
    * hasAccess
    */
    function hasAccess($theright){
        $flag=FALSE;
        if (isAdmin()) {
            return TRUE;
        }
        if (strpos($this->acl["world"],$theright) !== FALSE) {
            $flag = TRUE;
        } else {
        	if ($this->acl) {
            foreach ($this->acl as $access_class =>$rights) {
                if ((strpos($rights,$theright) !== FALSE) & (hasAccessClass($access_class))){
                    $flag=TRUE;
                }
            }
        	}
        }
        return $flag;
    }
    
    /**
    * Returns true if form owns the attribute indicated
    */    
    function hasAttribute($theattribute){
        return (strpos($this->attributes,$theattribute)!==FALSE);
    }
    
    /**
    * The message dispatcher
    *
    * Run methods of the form based on the $form_action parameter
    * Picks up content from object variables and passes them to the content variables.
    *
    * @param string $form_action The name of the action to perform (edit,submit,delete, ...)
    */
    function action($form_action){
    	//print "<br />Form action: $this->name";
    	if($form_action=='') $form_action='show';
        $accessflag=FALSE;
        $this->actionbar_content="";
        if ($form_action=="edit" & $this->hasAccess("w")==FALSE) $form_action="show";
        $this->action=$form_action;
        switch ($form_action) {
            case "delete":
                $accessflag=$this->hasAccess("w");
                break;
            case "edit":
                $accessflag=$this->hasAccess("w");
                $this->checkvalue=sprintf("%9d",rand(0,999999999));
                //echo "The random is $this->checkvalue";
                break;
            case "submit":
                $accessflag=$this->hasAccess("w");
                $checkflag=!$this->hasAttribute('c');
                if ($checkflag){
                	//echo "Checking {$this->name}";
                	$stored=$this->checkvalue;
                	$submitted=$GLOBALS['req_form_check_value'];
                	if ($stored!=$submitted){
                		trigger_error("Form Check value mismatch! stored value: $stored submitted value: $submitted",E_USER_ERROR);
                	} else {
                		//echo "<br />Form checkout value OK";
                	}
                }
                break;
            case "show":
                $accessflag=$this->hasAccess("r");
                break;
            default:
                $accessflag=TRUE;
        }

        if ($accessflag){
        	$this->translate();
            /* if ($this->hasAccess("w") & $this->hasAttribute('e') & ($form_action=='show')){            
                $this->actionbar->fields['edit']->setVisible(TRUE);
            } else {
                $this->actionbar->fields['edit']->setVisible(FALSE);               
            }*/
            if ($form_action<>""){
            	if ($form_action=='edit') $this->error='';
				// If the form_new flag is set we need to set each field to it's default value
				if (count($this->fields)>0){				
					if ((($this->hasAttribute('n')) or ($GLOBALS['req_form_new']<>'')) and ($form_action=='edit')){						
						foreach ($this->fields as $thefieldname => $thefield) {
							$this->fields[$thefieldname]->setDefaultValue();
							$val=$this->fields[$thefieldname]->getValue();

						}
					}else{
						foreach ($this->fields as $thefieldname => $thefield) {
							$this->fields[$thefieldname]->setReqValue();
						}
					}
				}						
                if ($this->before_action_func<>""){
                    $thefunc=$this->before_action_func;
                    $rv=$this->module->$thefunc($this,$form_action);
                    if ($rv) return $rv;
                }

                $thefunction="On_$form_action";
                $returnvalues = $this->$thefunction();
                if ($this->after_action_func<>""){                    
                    $thefunc=$this->after_action_func;
                    $this->module->$thefunc($this,$form_action);
                }
            }
            else
                $this->On_default();
        }
        else {
            if (!$this->hasAttribute('w')) {
                $this->title="Access denied";
                $this->content="<b>Access Denied!</b><br /> You do not have sufficient access rights for this operation";
            }
        }
        if ($this->content!=""){
        	    //echo "<br />The form is $this->name";
        	    //print_r($this);
                $this->boxtemplate->setOwner($this);
                $this->boxtemplate->clearContent();
                $this->boxtemplate->processTag("header");
                $this->boxtemplate->processTag("body");
                $this->boxtemplate->processTag("footer");
                $GLOBALS['content'][$this->contentvar] = $this->boxtemplate->getContent();
                if ($this->contentvar=='CNT_main') {
                	$GLOBALS['content']['CNT_title']=$this->title;
                }                
                $this->boxtemplate->clearContent(); // prevent it to use space in session store                
        }
        return $returnvalues;
    }
    function cleanup(){
        unset($this->content);
        unset($this->actionbar_content);
        //unset ($this->boxtemplate->content);
    }
    /**
    * Get the HTML code which contains the whole title with navigation bar and action bar
    * @return string The HTML code for title+navigation bar + action bar
    */
    
    function getTitleWithToolBars(){
        if ($this->title<>"") {
            $toolbarcontent=$this->getNavBar() . $this->actionbar_content;
            return "<table class=\"mod-title\"><tr><th align=\"left\" class=\"mod-title\">$toolbarcontent</th><th class=\"mod-title\">$this->title</th><th class=\"mod-title\"></th></tr></table>";            
        }
    }
    
    /**
    * Get content of the action bar
    * @return string The HTML code for the action bar
    */    
    function getActionBar(){
        $module=$this->modulename;
        $formname=$this->name;
        
        if ($this->hasAttribute('e') & $this->action<>'edit'){
            $this->actionbar->fields['edit']->setLink("index.php?module=$module&amp;" . $module . "_op=form&amp;form_name=$formname&amp;form_action=edit&amp;foreign_key_name=$this->foreign_key_name&amp;foreign_key_value=$this->foreign_key_value");
            $this->actionbar->fields['edit']->setVisible($this->hasAccess('w'));            
        } else {
            $this->actionbar->fields['edit']->setVisible(FALSE);
        }
        
        if ($this->hasAttribute('d') & $this->action<>'edit'){
            $this->actionbar->fields['delete']->setLink("index.php?module=$module&amp;" . $module . "_op=form&amp;form_name=$formname&amp;form_action=delete&amp;foreign_key_name=$this->foreign_key_name&amp;foreign_key_value=$this->foreign_key_value");
            $this->actionbar->fields['delete']->setExtraHTMLAttributes("onClick=\"return confirm('Do you really want to delete this entry?');\"");
            $this->actionbar->fields['delete']->setVisible($this->hasAccess('w'));
        }
        else {
            $this->actionbar->fields['delete']->setVisible(FALSE);
        }        
        return $this->actionbar->Action('show');
    }

    /**
    * Get content of the navigation bar
    * @return string The HTML code for the navigation bar
    */    
    function getNavBar(){
        if (($this->contentvar == $this->module->contentvar) and $this->_parent_form_name<>'') {        	
	        $this->navbar->fields['back']->setVisible(TRUE);	        
	        list($module,$form,$action)=$this->decodeFormID($this->_parent_form_name);
    	    $this->navbar->fields['back']->setLink(formURL($module,$form,$action));        	            
        } else {
            $this->navbar->fields['back']->setVisible(FALSE);
        }        
        return $this->navbar->Action('show');
    }
    
    /**
    * Get the title of this form
    * @return string The title of this form
    */
    function getTitle(){
        return $this->title;
    }
    
    /**
    * Set the content variable for this form
    * @param string $value The new value for the content variable
    */
    function setContentvar($value){
        $this->contentvar = $value;
    }
    
    /**
    * Get the content variable for this form
    * @return string The value of the content variable
    */
        function getContentvar(){
        return $this->contentvar;
    }    
    
    /**
    * Add a field to the form
    * @param TField $afield The field to be added
    */
    function addField($afield){
        $this->fields[$afield->name]= $afield;
    }
    
    /**
    * Add several fields to the form
    *
    * This replaces the construct that passed an array of fieldnames to the form's constructor
    * This function has a variable number of parameters
    * TODO: finish this!
    */
    function addFields(){
        $fields = func_get_args();
        foreach($fields as $field_description) {
            list ($name,$type,$title,$size,$default,$choices,$attributes)=explode(":",$field_description);
            switch ($type) {
                case "date" :
                    $field=&new TDateField($this,$name,$title,$default,$attributes);
                    break;
                case "boolean" :
                    $field=&new TBooleanField($this,$name,$title,$default,$attributes);
                    break;
                case "detailbutton":                    
                    break;
                
                case "dropdown":
                    $choicesarray = explode(",",$choices);
                    $field = & new TDropDownField($this,$name,$title,$choicesarray,$default,$attributes);
                    break;
                
                case "icons":
                    break;
                
                case "progressbar":
                    break;
                                
                case "radio":
                    break;
                
                case "sqldropdown":
                    $field =&new TSqlDropDownField($this,$name,$title,$choices,$default,$attributes);
                    break;
                
                case "statusicon":
                    break;
                
                case "textarea":
                    list($rows,$cols)=explode(',',$size);
                    $field=&new TTextAreaField($this,$name,$title,$rows,$cols,$default,$attributes);                    
                    break;
                
                case "password":
                    $field=&new TPasswordField($this,$name,$title,$size,$default,$attributes);
                    break;
                default:
                    $field=&new TStringField($this,$name,$title,$size,$default,$attributes);
            }
            $this->AddField($field);            
        };    
    }
    
    // placeholder
    function on_Edit(){
    }
    
    function on_Error(){
    	   $this->on_Edit();
    }
    
    /**
    * Show the form
    * Just call On_edit by default
    * @see On_edit
    */
    function on_Show() {
        $this->on_Edit();
    }
    /**
    * Determine if this form always sho on the form (a 'block' or static form)
    * @return True if this form is static (e.g. it shows on every page)
    */
    function getShowAlways() {
        return $this->hasAttribute('s');
    }
    
    /**
    * Get the URL that will retrieve this form
    */
    function getURL(){
        return "/index.php?module={$this->module->name}&{$this->module->name}_op=form&form_name={$this->name}&form_action={$this->action}";
    }
}


/**
* Simple form
*
* Needs submit handler to be useful
*/

class TSimpleForm extends TForm {

    /**
    * The function to be called after sumbit
    */
    var $submitfunction;
    
    /**
    * Foreign key value
    */
    var $foreign_key_value;
    
    /**
    * Constructor
    */
    function TSimpleForm(&$themodule,$form_name,$thetitle,$theattributes="",$the_parent_form_name="",$thesubmitfunction="",$thepreparefunction=""){
        $this->TForm($themodule,$form_name,$thetitle,$theattributes . 'n',$the_parent_form_name);
        $this->submitfunction=$thesubmitfunction;
        $this->preparefunction=$thepreparefunction;
    }
    
    /**
    * Action in response to edit message
    *
    * Builds an edit form based on previously stored form info
    * - Outputs the prologue;
    * - Constructs a table with one field description/edit field per row
    * - Adds an OK and Cancel button at the bottom of the form
    * - Adds hidden fields for modulename, formname,foreign_key_value
    * - Adds a fixed hidden field form_action submit: The On_submit method will be called upon hitting OK or cancel
    * - In case $form_new is set show default values (no select query)
    * - Outputs the $epilogue
    */
    function on_Edit(){
        global $theme;
        
     	if ($this->preparefunction<>"") {
            $preparefunction=$this->preparefunction;
            $this->module->$preparefunction(&$this);
        }
        
        /*
        * Show each field with it's title
        * remember if any field needs a multipart form (e.g. file upload)        
        */
        $content .= "<table width=\"100%\" class=\"$this->style\">";
        if ($this->fields){
	        foreach ($this->fields as $fieldname => $field) {
	            $fieldtitle=$field->getTitle();
	            $content .= "<tr><td class=\"$this->style\">$fieldtitle</td><td class=\"$this->style\">";
	            //$content .= $this->fields[$fieldname]->Action("edit",$this->row[$field->dbname]);
				$content .= $this->fields[$fieldname]->Action("edit");
	            $content .= "</td></tr>";
	            if ($field->hasAttribute('m')) {$needs_multipart=TRUE;}
	        }
	    }
        $content .= "<tr></tr>";
        $okbutton=firstExistingFile("themes/$theme/img/ok_button.png");
        $cancelbutton=firstExistingFile("themes/$theme/img/cancel_button.png");
        $content .= "<tr><td class=\"$this->style\" colspan=\"2\" ><input type=\"image\" name=\"okbutton\" value=\"OK\" src=\"$okbutton\" /> &nbsp; <input type=\"image\" name=\"cancelbutton\" value=\"Cancel\" src=\"$cancelbutton\" /></td></tr>";
        
        $content .= "</table>";
        
        // TODO needs to change to follow the new convention. !
        $content .=hiddenField("module",$this->modulename);
        $content .=hiddenField("form_name",$this->name);
        $content .=hiddenField("form_action","submit");
        $content .=hiddenField("form_check_value",$this->checkvalue);
        
        
        $action_tag=$this->modulename . "_op";
        $content .=hiddenField($action_tag,"form");
        $content .=$this->epilogue;
        if ($needs_multipart){$enctype="ENCTYPE=\"multipart/form-data\"";}
        $this->content = "<form name=\"Edit\" action=\"index.php\" method=\"post\" $enctype>$content</form>";
    }
    /**
    * Action in response to edit message
    *
    * Builds an edit form based on previously stored form info
    * Like On_edit but less complicated as there is no info to save in the form
    */
    function on_Show(){
    	 
    	 if ($this->preparefunction<>"") {
            $preparefunction=$this->preparefunction;
            $this->module->$preparefunction(&$this);
        }    	
                
        $content = "<table class=\"$this->style\">";

        foreach ($this->fields as $fieldname => $field) {
            $fieldtitle=$field->getTitle();
            $content .= "<tr><th class=\"$this->style\"><b>$fieldtitle</b></th><td class=\"$this->style\" >";
			$content .= $this->fields[$fieldname]->Action("show");              
            $content .= "</td></tr>";
        }
        $content .= "</table>";

        $content .=$this->epilogue;
        $this->content=$content;
    }
    
    function on_Submit(){
        $thebutton=getButton();
        if ($this->submitfunction<>"") {
            $submitfunc=$this->submitfunction;
            $returnvalues = $this->module->$submitfunc(&$this);
            if ($returnvalues!=NULL) { return $returnvalues;}
        }
        // clear the new form flag, otherwise all shows up as empty
        $GLOBALS['req_form_new']="";

        $themodule=$this->module->name;
        // force using the foreign key values saved in the object by clearing the request vars 
        $GLOBALS['req_foreign_key_name']="";
        $GLOBALS['req_foreign_key_value']='';
                
        if ($this->jumplist[$thebutton]) {
        	$formid=$this->jumplist[$thebutton];
        } else {
        	$formid=$this->_parent_form_name;
        }
        return $this->decodeFormID($formid);
    }
}    


/**
* The Database aware form Class
*
* @author Joost Horward
* @access public
* @package catviz
*/

class TDBForm extends TForm {
    /**
    * The name of a foreign key (or record id) that this form is processing
    * @var string 
    */
    var $foreign_key_name;
    /**
    * The value of the foreign key
    * @var integer 
    */
    var $foreign_key_value;

    /**
    * True if this form represents a new entry
    * @var integer 
    */
    var $form_new;

    /**
    * Holds the name of the keyfield for this form
    * @var string 
    */
    var $keyfield;
    
    /**
    * Detail tables depending on this form
    * Keys: name, can_delete
    */
    var $detailtables;
    
    /**
    * Holds the current query used by the form
    * @var string
    */
    var $query;
    
    /**
    * Holds the current row retrieved from the query
    * @var array
    */
    var $row;

    /**
    * Constructor of TDBForm
    *
    * Store the variables from the parameters and
    * initialize the fields array of field objects
    *
    *
    * @author Joost Horward
    * @access public
    * @param string $themodule Ref to the parent module
    * @param string $form_name Name of this form
    * @param string $thetable
    */
    function TDBForm(&$themodule,$form_name,$thetitle,$the_foreign_key_name="",$theattributes="",$the_parent_form_name="",$thekeyfield="id"){
    	//echo "The keyfield (TDBform) is $thekeyfield";
        $this->TForm($themodule,$form_name,$thetitle,$theattributes,$the_parent_form_name);
        $this->foreign_key_name=$the_foreign_key_name;
        $this->foreign_key_value=-1;
        $this->keyfield=$thekeyfield;
        $this->detailtables=array();
        $this->persistentproperties[]='foreign_key_value';
    }

    /**
    * The message dispatcher
    *
    * Run methods of the form based on the $forn_action parameter
    * Picks up content from object variables and passes them to the content variables.
    *
    * @param string $form_action The name of the action to perform (edit,submit,delete, ...)
    */
    function action($form_action){
		$this->form_new=$GLOBALS['req_form_new'];	
        if ($GLOBALS['req_foreign_key_value']!="") {
            $this->foreign_key_value=$GLOBALS['req_foreign_key_value'];
        }
        if ($GLOBALS['req_foreign_key_name']<>""){
            $this->foreign_key_name=$GLOBALS['req_foreign_key_name'];
        }
        return parent::action($form_action);
    }
    
    function constructQuery(){
	    $this->query="select ";
        foreach ($this->fields as $fieldname => $field) {if ($field->dbname<>"") { $this->query .= $field->dbname . ","; } };
        $this->query .= "$this->keyfield from $this->table where $this->keyfield= $this->foreign_key_value";
    }
    
    
    /**
    * Add a table name to this form that depends on this form
    * so that appropriate action can be taken when the master row
    * is deleted
    */
    function addDetailTable($name,$can_delete=FALSE){
        $this->detailtables[$name]=$can_delete;
    }
    
    function cleanup(){
    	parent::cleanup();
    	unset($this->row);
    }  
}

/**
* TMultiForm : a form that holds DBForms
* Primarily used to have master/detail views
*
*/

class TMultiForm extends TDBForm {
        
    /**
    * @var array An array holding the forms that make up this MultiForm
    */
    var $forms;
    
    /**
    * Constructor
    */    
    function TMultiForm (&$themodule,$form_name,$thetitle,&$theforms,$theattributes,$the_parent_form_name="",$thekeyfield="id"){
    	//echo "The keyfield is $thekeyfield";
        TDBForm::TDBForm(&$themodule,$form_name,$thetitle,"",$theattributes,$the_parent_form_name,$thekeyfield);
        $this->forms = &$theforms;
        $this->statictitle = 'MultiForm';
        $this->style="multi-layout";
    }
    
    /**
    * Generate the HTML that makes up the MultiForm
    * Creates a one-column table that holds each form title/contentin a cell.
    */
    function on_Show() {        
        $this->content = "<table class=\"$this->style\">";
        // Watch Out! Can't use $theform as it is a COPY of the actual form!!
        foreach ($this->forms as $formindex =>$theform) {
        	if ($GLOBALS['req_foreign_key_value']<>'') { 
            	$this->forms[$formindex]->foreign_key_value=$GLOBALS['req_foreign_key_value'];
            }
            $this->forms[$formindex]->Action("show");          
            $toolbarcontent=$this->forms[$formindex]->getActionBar();
            $title=$this->forms[$formindex]->getTitle();

            $this->content .= "<tr><th class=\"$this->style\"><table class=\"mod-title\"><tr><th align=\"left\" class=\"mod-title\">$toolbarcontent</th><th class=\"mod-title\">$title</th><th class=\"mod-title\"></th></tr></table></th></tr>";            

            $this->content .= "<tr><td class=\"$this->style\">"  . $this->forms[$formindex]->content . "</td></tr>";
        }
        $this->content .= "</table>";        
    }
    
    /*
    * Just show the MultiForm, you cannot actually edit in a MultiForm ...
    */
    function on_Edit() {
        $this->on_Show();
    }
}

class TTabbedMultiForm extends TMultiForm {
        
    /**
    * @var array An array holding the forms that make up this MultiForm
    */
    var $forms;
    var $tab;
    
    /**
    * Constructor
    */    
    function TTabbedMultiForm (&$themodule,$form_name,$thetitle,&$theforms,$theattributes,$the_parent_form_name="",$thekeyfield="id"){
    	$this->tab=1;
    	$this->persistentproperties=array('tab');
        TMultiForm::TMultiForm(&$themodule,$form_name,$thetitle,&$theforms,$theattributes,$the_parent_form_name,$thekeyfield);
        $this->statictitle = 'TabbedForm';
        $this->style="multi-layout";        
    }
    
    /**
    * Generate the HTML that makes up the TabbedMultiForm
    * Creates a one-column table that holds each form title/content in a cell.
    */
    function on_Show() {        
        $this->content = "<table class=\"$this->style\">";
        // Watch Out! Can't use $theform as it is a COPY of the actual form!!
        
        //
        // Show the form titles as tabs
        $this->content .= "<tr><th align=\"left\" class=\"$this->style\">&nbsp;";
        $module=$this->module->name;
        if ($GLOBALS['req_foreign_key_value']<>'') {
        	//$this->tab=1;
        }
        
        $tabindex=$this->tab;
        if ($_REQUEST['form_tab']<>'') {
        	$tabindex=$_REQUEST['form_tab'];
        	$this->tab=$tabindex;
    	} else {
    		$tabindex=$this->tab;
        }
                
        foreach ($this->forms as $formindex =>$theform) {        	
            $title=$this->forms[$formindex]->getTitle();
            if ($GLOBALS['req_foreign_key_value']<>'') { 
            	$this->forms[$formindex]->foreign_key_value=$GLOBALS['req_foreign_key_value'];
            };
            $title=$this->forms[$formindex]->getTitle();
			if ($formindex==$tabindex) {
				$class='activetab';
			} else {
				$class='tab';
			}
            $this->content .="<a class=\"$class\" href=\"index.php?module=$module&amp;{$module}_op=form&amp;form_name={$this->name}&amp;form_action=edit&amp;form_tab=$formindex&amp;foreign_key_value={$GLOBALS['req_foreign_key_value']}\">$title</a>&nbsp;|&nbsp;";
        }
        
        $this->content .= '</th></tr>';


        $this->forms[$tabindex]->Action("show");  
        
        $toolbarcontent=$this->forms[$tabindex]->getActionBar();
        $title=$this->forms[$tabindex]->getTitle();
        
        $this->content .= "<tr><th class=\"$this->style\"><table class=\"mod-title\"><tr><th align=\"left\" class=\"mod-title\">$toolbarcontent</th><th class=\"mod-title\">$title</th><th class=\"mod-title\"></th></tr></table></th></tr>";            

        $this->content .= "<tr><td class=\"$this->style\">"  . $this->forms[$tabindex]->content . "</td></tr>";
        $this->content .= "</table>";        
    }
    
    /*
    * Just show the MultiForm, you cannot actually edit in a MultiForm ...
    */
    function on_Edit() {
        $this->on_Show();
    }
}

/**
* A Database Edit Form
*
* This class is a database entry form
*
* This class can be used directly to create simple forms, or to derive other form
* types from. Standard behaviour is supplied for submit and delete actions.
*
* @author Joost Horward
* @access public
*/

class TDBEditForm extends TDBForm{
    /**
    * The name of the table that is edited by this form.
    * @var string 
    */
    var $table;

    /**
    * Holds the current row retrieved from the database
    *
    * This is held in the class because that allows the fields to access the row
    * otherwise they could only access their 'own' value, sometimes it is useful to
    * do this.
    * @var array
    */
    var $row;

    /**
    * Constructor of TDBEditForm
    *
    * Store the variables from the parameters and
    * initialize the fields array of field objects
    *
    *
    * @author Joost Horward
    * @access public
    * @param string $themodule Ref to the parent module
    * @param string $form_name Name of this form
    * @param string $thetable The name of the table that is edited by this form    
    * @param string $theattributes Attributes of this form
    * @param string $thekeyfield The key value for this form
    */
    function TDBEditForm(&$themodule,$form_name,$thetitle,$thetable,$theattributes="",$the_parent_form_name="",$thekeyfield="id"){
        $this->TDBForm($themodule,$form_name,$thetitle,"",$theattributes,$the_parent_form_name,$thekeyfield);
        $this->table=$thetable;
        $this->row=array();
    }
    

    /**
    * Action in response to edit message
    *
    * Builds an edit form based on previously stored form info
    * - Assembles and runs a SELECT query
    * - Outputs the prologue;
    * - Constructs a table with one field description/edit field per row
    * - Adds an OK and Cancel button at the bottom of the form
    * - Adds hidden fields for modulename, formname,foreign_key_value
    * - Adds a fixed hidden field form_action submit: The On_submit method will be called upon hitting OK or cancel
    * - In case $form_new is set show default values (no select query)
    * - Outputs the $epilogue
    */
    function on_Edit(){
        global $theme;

        // Build the query
        if ($this->form_new=="") {
        	$this->constructQuery();
            $result=runquery($this->query);
            $this->row = mysql_fetch_array($result);
            foreach ($this->fields as $fieldname => $field) {if ($field->dbname<>"") { $this->fields[$fieldname]->setValue($this->row[$field->dbname]); } };
            
        }else{
			$this->row=array();
		}
		$content .="<p/>{$this->error}";
        $content .= "<table class=\"$this->style\">";
        foreach ($this->fields as $fieldname =>$field) {
            $fieldtitle=$field->getTitle();
            // Hidden fields processed but not displayed
            if ($this->fields[$fieldname]->getVisible()){
                $content .= "<tr><td class=\"$this->style\">$fieldtitle</td><td class=\"$this->style\">";            
                $content .= $this->fields[$fieldname]->Action("edit");
                $content .= "</td></tr>";
            } else {
                $this->fields[$fieldname]->Action("edit");
            }
            if ($field->hasAttribute('m')) {$needs_multipart=TRUE;}
        }
        $content .= "<tr></tr>";
        $okbutton=firstExistingFile("themes/$theme/img/ok_button.png");
        $cancelbutton=firstExistingFile("themes/$theme/img/cancel_button.png");
        $content .= "<tr><td class=\"$this->style\" colspan=\"2\" ><input type=\"image\" name=\"okbutton\" value=\"OK\" src=\"$okbutton\" /> &nbsp; <input type=\"image\" name=\"cancelbutton\" value=\"Cancel\" src=\"$cancelbutton\" /></td></tr>";
        
        $content .= "</table>";
        
        // needs to change to follow the new convention. !        
        $content .=hiddenField("module",$this->modulename);        
        $content .=hiddenField("form_name",$this->name);
        $content .=hiddenField("form_action","submit");
        $content .=hiddenField("form_check_value",$this->checkvalue);
        
        // Need to carry the foreign key forward to the submit function
        if ($this->foreign_key_value<>"") {
            $content .=hiddenField("foreign_key_name",$this->foreign_key_name);
            $content .=hiddenField("foreign_key_value",$this->foreign_key_value);
        }
        if ($this->form_new) {
            $content .=hiddenField("form_new","true");
        }
        $action_tag=$this->modulename . "_op";
        $content .=hiddenField($action_tag,"form");
        $content .=$this->epilogue;
        if ($needs_multipart){$enctype="ENCTYPE=\"multipart/form-data\"";}
        $this->content = "<form name=\"Edit\" action=\"index.php\" method=\"post\" $enctype>$content</form>";        
    }
    /**
    * Action in response to edit message
    *
    * Builds an edit form based on previously stored form info
    * Like On_edit but less complicated as there is no info to save in the form
    */
    function on_Show(){
        // Build the query
        $this->constructQuery();
        $result=runquery($this->query);
        $this->row = mysql_fetch_array($result);
        $content = $this->prologue;        
        $content .= "<table class=\"$this->style\">";

        foreach ($this->fields as $fieldname => $field) {
            $fieldtitle=$field->getTitle();
            $content .= "<tr><td class=\"$this->style\"><b>$fieldtitle</b></td><td class=\"$this->style\">";
            $content .= $this->fields[$fieldname]->Action("show",$this->row[$field->name]);       
            $content .= "</td></tr>";
        }
        $content .= "</table>";
        $content .=$this->epilogue;
        $this->content=$content;
        $this->actionbar_content=$this->getActionBar();
    }


    /**
    * Submit a form - store values (in database)
    *
    * This gets called after an On_edit when OK or Cancel is clicked
    */
    function on_Submit() {
        $thebutton=getButton();
        if ($thebutton=="OK" & $this->hasAccess('w')) {
            $table =$this->table;
            if ($this->form_new=="") {
                $thequery =  "update $table SET ";
            }
            else {
                $thequery= "insert $table SET ";
                if (($this->foreign_key_name<>"") & ($this->foreign_key_name<>$this->keyfield)) {
                    $thequery .="$this->foreign_key_name=$this->foreign_key_value, ";
                }
            }
            foreach ($this->fields as $fieldname => $field) {
                $module_name=$this->module->name;
                $fieldname=$field->name;                
                $dbfieldname=$field->dbname;
                if (($dbfieldname<>"") & !$this->fields[$fieldname]->hasAttribute('s')){
                    $varname = "tmodule_" . $fieldname;
                    $value=$this->fields[$fieldname]->getValue();
                    $thequery .= "$dbfieldname = \"$value\",";
                }
            }
            // chop the last comma off
            $thequery=substr($thequery,0,-1);
            if ($this->form_new=="") {
                $thequery .= " where $this->keyfield=$this->foreign_key_value";
            }
            //echo "<br />$thequery";
            $result=runquery($thequery);
            if ($result==FALSE) {                
                $GLOBALS['content']['statusline']="[Error: There was an error while processing the query]<br />$thequery";
            } else {                
                $GLOBALS['content']['statusline']="[OK: The database has been updated]";
            }
        } else {            
            $GLOBALS['content']['statusline']="[Cancel: The action has been cancelled]";
        } 
        
         if ($this->jumplist[$thebutton]) {
        	$formid=$this->jumplist[$thebutton];
        } else {
        	$formid=$this->_parent_form_name;
        }
        return $this->decodeFormID($formid);
        
        
        
        
               
    }
    
    /**
    * Delete the entry with $foreign_key_value equals the keyfield 
    */
    function on_Delete(){
        if (count($this->detailtables)>0) {
            $flag=FALSE;
            foreach ($this->detailtables as $name =>$can_delete){                
                if ($can_delete != TRUE){
                    $query="select count(*) from $name where $this->keyfield = $this->foreign_key_value";
                    $result=runquery($query);
                    $row = mysql_fetch_array($result);
                    $numrows=$row['count(*)'];
                    if ($numrows>0){
                        $flag=TRUE;
                        $message .= "<br />$numrows row(s) in detail table <b>$name</b> depend(s) on master table <b>$this->table</b> from which you tried to delete a row.";
                        $message .= "<br /><br />Please delete these entries before deleting the master table row.";                        
                    }  
                }
            }
            if ($flag){
                // TODO not a very 'clean' solution. In fact we are just pushing a dummy value.
                //$_SESSION['stack']->push(array('dummy','dummy','dummy',''));
                $this->content=$message;
                return array('','','');
            }else{
                foreach ($this->detailtables as $name =>$can_delete){                
                    if ($can_delete){
                        $query="delete from $name where $this->keyfield = $this->foreign_key_value";
                        $result=runquery($query);
                        $message .= "<br /> Removed detail rows from $name";
                     }
                }
            }
        }
        // get values from this row one more time - this enables the deletion of related files
        $query="select ";
        foreach ($this->fields as $field) {if ($field->dbname<>"") { $query .= $field->dbname . ","; } };
        $query .= "$this->keyfield from $this->table where $this->keyfield= $this->foreign_key_value";
        $result=runquery($query);
        $this->row = mysql_fetch_array($result);

        // Tell each field to clean up as it's row is about to be deleted
        foreach ($this->fields as $fieldname =>$field) {
            $this->fields[$fieldname]->Action("delete",$this->row[$field->name]);                   
        }
        
        // now we can delete the row itself.
        
        $table=$this->table;
        $thequery="delete from $table where $this->keyfield= $this->foreign_key_value";
        $result=runquery($thequery);
        if ($result==FALSE) {
            $this->content="An error occurred while deleting entry $this->foreign_key_value";
        }
        else {
            $this->content=$message;
            $this->content.="<br />$this->foreign_key_value has been deleted.";
        }
    }
    function cleanup(){
    	parent::cleanup();
    	unset($this->row);
    }
}

class TTemplate {
    var $form;
    var $templatefilename;
    var $template;
    var $tag;
    var $content;
    
    // Error handling needs to be added ! (in case no closing tag in particular)
    function TTemplate(&$theform,$thefilename){
        $this->form=&$theform;
        $this->templatefilename=$thefilename;

        $templatefile = fopen ($thefilename, "r");
        if ($templatefile)
	        {
	        while (!feof ($templatefile)) {
	            $this->template .= fgets($templatefile, 4096);
	        }
	        fclose ($templatefile);
        } else {
        trigger_error("Could not open template $thefilename");
        }         
        // find the tags
        $offset=0;
        while (($pos=strpos($this->template,'<!--&',$offset))!==FALSE) {                
            $endpos=strpos($this->template,'-->',$pos);
            $tagname=substr($this->template,$pos+5,$endpos-$pos-5);
            $closingtag='<!--/' . $tagname;
            $closingtagoffset=strpos($this->template,$closingtag,$pos);             
            $this->tag[$tagname]=substr($this->template,$endpos+3,$closingtagoffset-$endpos-3);
            $offset=$closingtagoffset+strlen($tagname)+7;
        }
        $this->content="";
        $this->style="ttemplate-layout";
        unset($this->template);
    }
    
    function setOwner(&$theform){
        $this->form=&$theform;
    }
    
    function processTag($tagname,$defaultfunc="edit") {        
        $template=$this->tag[$tagname];
        
        $offset=0;
        while ($offset<strlen($template)) {                
            if (($pos=strpos($template,'{',$offset))!==FALSE){
                $content .= substr($template,$offset,$pos-$offset);
                $action=$template[$pos+1];
                $endpos=strpos($template,'}',$pos+2);
                switch ($action) {
                case '!' :
                    $name=substr($template,$pos+2,$endpos-$pos-2);
                    $name=trim($name);
                    $ev='$value=$this->form->' . $name . ';';
                    eval($ev);
                    break;
                case '*' :
                    $name=substr($template,$pos+2,$endpos-$pos-2);
                    $name=trim($name);
                    $ev='$value='. $name . ';';
                    eval($ev);
                    break;
                // By default assume fieldname.function format
                default:
                    $name=substr($template,$pos+1,$endpos-$pos-1);
                    $name=trim($name);
                    $dotpos=strpos($name,'.');
                    if ($dotpos===FALSE){
                        $funcname=$defaultfunc;
                        $fieldname=$name;
                    } else {
                        $fieldname=substr($name,0,$dotpos);
                        $funcname=substr($name,$dotpos+1,strlen($name)-$dotpos-1);
                    }
                    //$fieldval=$this->form->row[$fieldname];                    
                    //$ev="\$value = \$this->form->fields[\$fieldname]->Action(\$funcname,\$fieldval);";
                    $ev="\$value = \$this->form->fields[\$fieldname]->Action(\$funcname);";       
                                 
                    eval($ev);
                    break;
                }
                $content .= $value;
                $offset=$endpos+1;
            } else {
                $content .= substr($template,$offset,strlen($template)-$offset);
                $offset = strlen($template);
            }
        }
        $this->content .=$content;
    }
    
    /**
    * Clear the template's content buffer
    */
    function clearContent(){
        unset($this->content);
    }
    
    /**
    * Return the content of the template
    */
    function getContent (){
        return $this->content;
    }      
}


/**
* TPageTemplate
*/

class TPageTemplate {
    /**
    * The filename of the file that holds the template
    */
    var $templatefilename;
    
    /**
    * The template content itself
    */
    var $template;
    /**
    * The content buffer for this template
    */    
    var $content;
    /**
    * Constructor
    */
    function TPageTemplate($thefilename){
        $this->templatefilename=$thefilename;        
        $templatefile = fopen ($thefilename, "r");
        while (!feof ($templatefile)) {
            $template .= fgets($templatefile, 4096);
            $template .="\n";
        }
        fclose ($templatefile);        
        $this->template=explode('<!--block-->',$template);
        $this->content="";        
    }
    /**
    * Process the page template
    */    
    function processPageTemplate() {
    
        $templatefile = fopen ($this->templatefilename, "r");    
    
        $content="";
            
        
        foreach ($this->template as $template)
        {
            $offset=0;
            $block="";
            $clearflag=FALSE;
            while ($offset<strlen($template)) {                
                if (($pos=strpos($template,'{',$offset))!==FALSE){
                    $block .= substr($template,$offset,$pos-$offset);
                    $action=$template[$pos+1];
                    $endpos=strpos($template,'}',$pos+2);
    
                    switch ($action) {
                    //Execute something that returns a value
                    case '!' :
                        $name=substr($template,$pos+2,$endpos-$pos-2);
                        $ev='$value=' . $name . ';';
                        eval($ev);
                        break;
                    //Some value; if the value is nil the whole block disappears
                    case '=' :
                        $name=substr($template,$pos+2,$endpos-$pos-2);
                        $value=&$GLOBALS['content'][$name];                        
                        if ($value=="") { $clearflag=TRUE;}
                        break;
                    //Flag to switch on block; flag itself is not echoed out
                    case '*' :
                        $name=substr($template,$pos+2,$endpos-$pos-2);
                        $value=$GLOBALS['content'][$name];                        
                        if ($value=="") { $clearflag=TRUE;}
                        $value='';
                        break;
                    // By default assume content var name 
                    default:
                        $name=substr($template,$pos+1,$endpos-$pos-1);
                        $value=&$GLOBALS['content'][$name];
                        break;
                    }                
                    $block .= $value;
                    $offset=$endpos+1;
                } else {
                    $block .= substr($template,$offset,strlen($template)-$offset);
                    $offset = strlen($template);
                }
            }
            if (!$clearflag) {$content .= $block;}
        }
        $this->content =$content;
    }
    /**
    * Clear the page template's content buffer
    */
    function clearContent(){
        unset($this->content);
    }
    
    /**
    * Get the page template's content buffer
    */    
    function getContent (){
        return $this->content;
    }      
}

/**
* A Database Template Edit Form
*
* This class is a database entry form
*
* This class can be used directly to create simple forms, or to derive other form
* types from. Standard behaviour is supplied for submit and delete actions.
*
* @author Joost Horward
* @access public
*/

class TTemplateDBEditForm extends TDBEditForm{
    /**
    * Constructor of TDBForm
    *
    * Store the variables from the parameters and
    * initialize the fields array of field objects
    *
    *
    * @author Joost Horward
    * @access public
    * @param string $themodule Ref to the parent module
    * @param string $form_name Name of this form
    * @param string $thetable The name of the table that is edited by this form    
    * @param string $theattributes Attributes of this form
    * @param string $thekeyfield The key value for this form
    */
    
    /**
    * @var TTemplate Holds the current template object
    */
    var $template;
    
    function TTemplateDBEditForm(&$themodule,$form_name,$thetitle,$thetable,$theattributes="",$the_parent_form_name="",$thekeyfield="id"){
        $this->TDBEditForm(&$themodule,$form_name,$thetitle,$thetable,$theattributes,$the_parent_form_name,$thekeyfield);
    }
    
    function setTemplate(){
    	parent::setTemplate();
    	$themename=$GLOBALS['theme'];
        $filename=firstExistingFile("themes/$themename/mod_{$this->modulename}/{$this->name}.html","themes/$themename/mod/{$this->modulename}/templates/forms/{$this->name}.html","mod/{$this->modulename}/templates/{$this->name}.html");
        $this->template =& new TTemplate(&$this,$filename);
    }
    
    
    /**
    * Action in response to edit message
    *
    * Builds an edit form based on previously stored form info
    * - Assembles and runs a SELECT query
    * - Outputs the prologue;
    * - Constructs a table with one field description/edit field per row
    * - Adds an OK and Cancel button at the bottom of the form
    * - Adds hidden fields for modulename, formname,foreign_key_value
    * - Adds a fixed hidden field form_action submit: The On_submit method will be called upon hitting OK or cancel
    * - In case $form_new is set show default values (no select query)
    * - Outputs the $epilogue
    */
    function on_Edit(){
        /**
        * foreign_key_name This is a form parameter with the name of the foreign key if any
        * @global string 
        */

        // Build the query
        if ($this->form_new=="") {
            $this->constructQuery();
            $result=runquery($this->query);
            $this->row = mysql_fetch_array($result);
            foreach ($this->fields as $fieldname => $field) {if ($field->dbname<>"") { $this->fields[$fieldname]->setValue($this->row[$field->dbname]); } };
        }
    
        //$content = $this->prologue;
        $this->template->clearContent();
        $this->template->processTag("header");
        $this->template->processTag("body");
        $this->template->processTag("editcontrols");
        $this->template->processTag("footer");
        $content .= $this->template->getContent();
        $this->template->clearContent(); // prevent it to use space in session store
        
        //$content .=$this->epilogue;
        $this->content=$content;
        // Run the action method of the non-visible fields
        foreach ($this->fields as $fieldname => $field){
            if ($this->fields[$fieldname]->getVisible()==FALSE) { $this->fields[$fieldname]->Action('edit');}
        }
    }
    
    /**
    * Return the hidden fields for this form
    * @return string HTML code for the hidden fields for this form
    */
    function getHiddenStuff(){

        $content .=hiddenField("module",$this->modulename);
        //echo "<br />[gethiddenstuff] The form name is now {$this->name}";
        $content .=hiddenField("form_name",$this->name);
        $content .=hiddenField("form_action","submit");
        $content .=hiddenField("form_check_value",$this->checkvalue);
        //echo "The check value in gethiddenstuff is $this->checkvalue";
        
        // Need to carry the foreign key forward to the submit function
        if ($this->foreign_key_value<>"") {
            $content .=hiddenField("foreign_key_name",$this->foreign_key_name);
            $content .=hiddenField("foreign_key_value",$this->foreign_key_value);
        }
     
        if ($this->form_new) {
            $content .=hiddenField("form_new","true");
        }

        $action_tag=$this->modulename . "_op";
        $content .=hiddenField($action_tag,"form");

        return($content);
    }

    /**
    * Action in response to show message
    *
    * Builds an edit form based on previously stored form info
    * Like On_edit but less complicated as there is no info to save in the form
    */
    function on_Show(){
        
        // Build the query
        $this->constructQuery();
        $result=runquery($this->query);
        $this->row = mysql_fetch_array($result);        
        foreach ($this->fields as $fieldname => $field) {if ($field->dbname<>"") { $this->fields[$fieldname]->setValue($this->row[$field->dbname]); } };        
            
        //$content = $this->prologue;
        $this->template->clearContent();
        $this->template->processTag("header","show");
        $this->template->processTag("body","show");
        $this->template->processTag("footer","show");
        $content .= $this->template->getContent();
        $this->template->clearContent(); // prevent it to use space in session store
        $content .=$this->epilogue;
        $this->content=$content;
        $this->actionbar_content=$this->getActionBar();
        //foreach ($this->fields as $fieldname => $field) {if ($field->dbname<>"") { $this->fields[$fieldname]->setValue($this->row[$field->dbname]); } };
    }
}

/**
* Mother Of All Modules
*
* This is a base class that has base form functionality
* does message handling
* and is used to drive module classes from
*/

class TModule{
    /**
    * The name of this module
    * @var string 
    */
    var $name;
    
    /**
    * The title of this module
    * @var string 
    */
    var $title;
    
    /**
    * The forms in this module
    * @var array 
    */
    var $forms;
    
    /**
    * The username is kept to be able to detect user change and reprocess InitializeForms
    * @var string 
    */
    var $username;
    
    /**
    * Main Contentvar for this module
    */
    var $contentvar;
    
    /**
    * Access Control List
    * This is the default ACL for each form
    */
    var $acl;

    /**
    * Constructor for TModule class
    *
    * @author Joost Horward
    * @param string $module_name The name of the module
    * 
    *
    */    
    function TModule($module_name,$module_title=""){
        $this->name  = $module_name;
        if ($module_title=="") {
            $this->title = $module_name;
        } else {
            $this->title=$module_title;
        }
        $this->contentvar="CNT_main";
        // The following line is crucial. If PHP doesn't know it's an array
        // The session persistency is a mess !?
        $this->forms = array();
        $this->InitializeForms();
        $this->username="anonymous";
    }
    
    /**
    * Initialize the forms of this module
    * This needs to be overridden by descendant classes to make the module useful
    */
    function initializeForms(){
        $this->forms=array();            
    }
    
    function &form($formname){
    	//echo "Fetching form $formname";
        if (is_object($this->forms[$formname])==FALSE){
            $this->initializeForm($formname);
        }
        if ($this->forms[$formname]->name==""){
        	   $this->initializeForm($formname);
        }
        if (is_object($this->forms[$formname])==FALSE){
            trigger_error("Failed to create form object for form $formname");
            return NULL;
        }
        return ($this->forms[$formname]);
    }
    
    function cloneForm(&$f,$formname,$clonename,$clonetitle,$clone_parent_form_name) {
    	//echo "<br />Cloning $formname into $clonename";
    	$f=$this->form($formname);
    	$f->set('name',$clonename);
    	$f->set('title',$clonetitle);
    	$f->set('_parent_form_name',$clone_parent_form_name);
    	// Correct the form pointers of the fields; otherwise they'll point to the wrong form...
    	if ($f->fields) {    	
    		foreach ($f->fields as $fieldname=>$field) {
    			//echo "Changing form pointer in $fieldname";
    			$f->fields[$fieldname]->form=&$f;
    		}
    	}
    	$f->setTemplate();
    	/*if ($f->template){
    		$themename=$GLOBALS['theme'];
    		$filename=firstExistingFile("themes/$themename/mod_{$f->modulename}/$clonename.html","themes/$themename/mod/{$f->modulename}/templates/forms/$clonename.html","mod/{$f->modulename}/templates/$clonename.html",  "themes/$themename/mod_{$f->modulename}/$formname.html","themes/$themename/mod/{$f->modulename}/templates/forms/$formname.html","mod/{$f->modulename}/templates/$clonename.html");
        	$f->template =& new TTemplate($f,$filename);
    	}*/
    	//return $f;    	
    }
    
    /**
    * Placeholder function
    */
    
    function initializeForm($formname){
    	include "mod/{$this->name}/forms/{$formname}.php";
    	if (is_object($this->forms[$formname])) {
    		$this->forms[$formname]->loadPersistentProperties();
    	}else{
    		trigger_error("Form $formname failed tot initialize"); 
    	}
    }

    /**
    * action when 'Mod filename' is called
    * each action has a On_action method in the class.
    * @param string $thefunction The function to be performed
    */
    function action($thefunction){
        
        /**
        * Re-initialize the forms when a different user logs into this session
        */
        if ($this->username != $_SESSION['module']["mod_userman"]->user_name){
            $this->InitializeForms();
            $this->username = $_SESSION['module']["mod_userman"]->user_name;
            }
        
        $this->beforeAction();
        if ($thefunction<>""){
            $thefunction="On_$thefunction";
            $returnvalues=$this->$thefunction();                        
        } else
            $returnvalues = $this->On_default();
        $this->showStaticForms();
        $this->afterAction();
        
        //foreach ($this->forms as $formname=>$form){
        //    $this->forms[$formname]->cleanup();
        //}
        
        // Uncomment below to populate the debug block        
        /*
        $debug_content = "<b>Module : $this->name </b><br />";
        $debug_content .= "Stackpointer: {$_SESSION['stack']->stackpointer} <br />";
        $debug_content .= "Stack: <br />";
        foreach ($_SESSION['stack']->stack as $index =>$formname) { $debug_content .= "$index {$formname[0]} {$formname[1]} {$formname[2]}<br />";};
        $debug_content .="<br />";
        $GLOBALS["content"]["CNT_debug"] ="<table class=\"mod-layout\"><tr><th class=\"tform-layout\">Debug</th></tr><tr><td class=\"tform-layout\">$debug_content</td></tr></table>";
        */
        return $returnvalues;
    }

    /**
    * Placeholder function for default module behaviour
    */
    function on_Default(){
    }

    /**
    * Placeholder function for default module behaviour
    */
    function beforeAction(){
    }

    /**
    * Placeholder function for default module behaviour
    */
    function afterAction(){
    }
    
    /**
    * Search function placeholder. Returns an associative array
    * with max. '$limit' rows
    * tags: title, summary, url
    */    
    function search($searchstring,$limit=10){
        return array();
    }
    
    /**
    * Approval function placeholder. Returns an associative array
    * with max. '$limit' rows
    * @return array Items to be approved. Tags: title, summary, url
    */    
    function getApprovalItems($limit=10){
        return array();
    }
    
    /**
    * Form handler
    *
    * This method is called when one of the form messages is received
    * (e.g. submit,edit,delete etc.)
    * Just calls the appropriate method
    */    
    function on_Form(){
        $form_name=$GLOBALS['req_form_name'];
        $GLOBALS['catviz_info']->current_form=$form_name;
        $form_action=$GLOBALS['req_form_action'];
        /*if ($this->forms[$form_name]==false) {
            $this->content="Error! Form $form_name is not defined";            
        } else {            
            return $this->forms[$form_name]->Action($form_action);            
        }*/
        if (is_object($this->form($form_name))){
            $form=&$this->form($form_name);
            return $form->action($form_action);
        } else {
            $this->content="Error! Form $form_name is not defined";
        }            
    }
    
    /**
    * Show the forms that have the s attribute set
    *
    * These are the allways-visible forms
    */
    function showStaticForms(){
        // note that the $formvalue is useless as it is a copy of the form.
        //We need to access the form itself otherwise changes will not persist
        if ($this->forms){
        foreach($this->forms as $formname => $formvalue) {
            if (is_object($this->forms[$formname])){
                if ($this->forms[$formname]->getShowAlways()){
                    $this->forms[$formname]->Action("show");              
                }
            }else{
                print "Not an object: $formname - value {$this->forms[$formname]}<br />";
            }
        }
        }
    }
    
    function __sleep(){
    	$a=get_object_vars($this);
    	foreach ($a as $property=>$value){
    		if ($property!='forms') {
    			$b[]=$property;
    		}
    	}
    	//echo "de schone slaper.";    	
    	return $b;
    }
    
}

/**
* List filter mini class - just holds filter info
*/
class TListFilter{
    var $form;
    var $name;
    var $title;
    var $filter_expression;
    var $value;
    /**
    * Constructor
    */
    function TListFilter(&$theform,$thename,$thetitle,$thefilter_expression,$thedefault=false){
        $this->name=$thename;
        $this->title=$thetitle;
        $this->filter_expression=$thefilter_expression;
        $this->value=$thedefault;
        $this->form=&$theform;
    }
    
    function update(){
        if ($_REQUEST["filter_{$this->form->name}_{$this->name}"]=='0') {
            $this->value=false;
        }
        if ($_REQUEST["filter_{$this->form->name}_{$this->name}"]=='1') {
            $this->value=true;
        }
    }
    
    function getValue(){
        $this->update();
        return $this->value;
    }
    
    function show(){
        $this->update();
        $uri=$this->form->getURL();
        if ($this->value){
            return "<a href=\"$uri&amp;filter_{$this->form->name}_{$this->name}=0 \"><img border=\"0\" src=\"lib/img/checkbox-checked.png\" alt=\"Filter ON\" /></a>&nbsp; $this->title";
        }else{
            return "<a href=\"$uri&amp;filter_{$this->form->name}_{$this->name}=1 \"><img border=\"0\" src=\"lib/img/checkbox.png\" alt=\"Filter OFF\" /></a>&nbsp; $this->title";
        }
    }        
}


/**
* List Form class
*
* This class creates a list view based on a query
* Displaying the fields is done by TFields show methods
* So any special fields will show up as intended
*
*/
class TDBList extends TDBForm {

    /**
    * Holds the query for this form
    * @var string 
    */
    var $query;
    
    /**
    * Holds the name of the form which is used to edit a line item of this list
    * @var string
    */
    var $detailform;
    
    /**
    * Holds the sort fields in sort order
    */
    var $list_order_fields;
    
    /**
    * Holds the filters for this list
    */
    var $filters;

    /**
    * TDBList
    * Constructor
    *
    * Just initialize variables;
    */
    function TDBList(&$themodule,$form_name,$thequery,$thetitle="List",$thedetailform="",$theforeignkey="",$theattributes='',$the_parent_form_name="",$thekeyfield="id"){
    	$this->list_order_fields=array();
        $this->TDBForm($themodule,$form_name,$thetitle,$theforeignkey,$theattributes,$the_parent_form_name,$thekeyfield);
        $this->persistentproperties[]='list_order_fields';
        $this->query=$thequery;
        $this->detailform=$thedetailform;
        $this->style="list-layout";
        
    }

    /**
    * Run the query and show the results
    *
    * This function does all the work
    * - Run query as normal
    * - Create a header using the TField object
    * - Call TField objects to show results
    *
    */

    function on_Show() {    
        $modulename=$this->module->name;
        
        if ($this->filters){
            /*
            * If the form has the f attribute it generates filter checkboxes
            */
            if ($this->hasAttribute('f')){
                $content .="<center>";
                foreach ($this->filters as $filtername =>$filter){
                    $content .= $this->filters[$filtername]->show();
                    $content .="&nbsp;";
                }            
                $content .="</center>";
            }
            
            foreach ($this->filters as $filtername =>$filter){
                if ($this->filters[$filtername]->value){
                    $filter_expression .= " {$this->filters[$filtername]->filter_expression} and";
                }
            }            
            
            $filter_expression=substr($filter_expression,0,strlen($filter_expression)-3);
        }
        if ($filter_expression=="") $filter_expression="1";
        
            
        $new_order=$_REQUEST['field_order'];
        if ($new_order){
            if ($this->list_order_fields[0]==$new_order) { $desc=" desc";}
            
            $oldkey=array_search($new_order,$this->list_order_fields);
            // remove the old one
            if ($oldkey!==FALSE){
                unset($this->list_order_fields[$oldkey]);
            }
            // even if it was descending
            $oldkey=array_search("$new_order desc",$this->list_order_fields);
            if ($oldkey!==FALSE){
                unset($this->list_order_fields[$oldkey]);
            }
            

            array_unshift($this->list_order_fields,"$new_order$desc");            
        }
        foreach ($this->list_order_fields as $theorder){
            $orderby .= "$theorder,";
        }
        $orderby=rtrim($orderby,',');
        if ($orderby=="") $orderby=1;
        
        $query=$this->query;
        $query=str_replace("FOREIGN_KEY",$this->foreign_key_value,$query);
        $query=str_replace("ORDER_BY",$orderby,$query);
        $query=str_replace("FILTER_EXPRESSION",$filter_expression,$query);
        $result=runquery($query);      

        /**
        * Build header
        */
        $this->row = mysql_fetch_array($result);
        if ($this->row) {
            $content .= "<table cellspacing=\"0\" class=\"$this->style\"><tr class=\"$this->style\">";            
            foreach ($this->fields as $fieldname => $field) {
                $fieldtitle=$this->fields[$fieldname]->getTitle();
                $content .= "<th class=\"$this->style\">$fieldtitle</th>";
            };
            $content .="</tr>";
            $rowcount=0;
            /**
            * build content rows
            */
            do {
            	$rowcount++;
            	if ($rowcount & 1)
                  $content .="<tr class=\"$this->style\">";
                else
                   $content .="<tr class=\"{$this->style}-even\">";
                foreach ($this->fields as $fieldname => $field) {                    
                    $value=$this->fields[$fieldname]->Action("show",$this->row[$field->dbname]);
		    if ($value=="") $value='&nbsp;';
                    $content .= "<td class=\"$this->style\">$value</td>";
                }
                $content .="</tr>";
            } while ($this->row = mysql_fetch_array($result));
            $content .="</table>";
            $this->content=$content;
        }
        else {
            $this->content = "<p>No records</p>";            
        }        
        $detailform=$this->detailform;
        $module=$this->module->name;
        $foreignkey=$this->foreign_key_name;
        if ($this->hasAccess("w") & $this->hasAttribute('a')){
            $this->actionbar->fields['addnew']->setLink("index.php?module=$module&amp;" . $module . "_op=form&amp;form_name=$detailform&amp;form_action=edit&amp;form_new=true&amp;foreign_key_name=$this->foreign_key_name&amp;foreign_key_value=$this->foreign_key_value");
            $this->actionbar->fields['addnew']->setVisible(TRUE);
        }
        else {
          if ($this->hasAttribute('a')) {$this->actionbar->fields['addnew']->setVisible(FALSE);}
        }
        $this->actionbar_content=$this->getActionBar();
    }

    /**
    * Just call On_show - a List form cannot be edited
    * @see on_Show
    */
    function on_Edit() {
        $this->on_Show();
    }
    /**
    * Set the list ordering. Parameters are db fieldnames; optionally followed by ' desc' EXACTLY ONE space preceding desc in lower case
    */
    function setOrder(){
        $this->list_order_fields = func_get_args();
    }
    /**
    * Add a filter to the list
    * @param string $name The name of the filter
    * @param string $title The title of the filter (shown to the user)
    * @param string $filter_expression The filter expression
    * @param string $default_value The default state of the filter, TRUE=ON
    */
    
    function addFilter($name,$title,$filter_expression,$default_value){
        $this->filters[$name]=&new TListFilter($this,$name,$title,$filter_expression,$default_value);
    }
    function cleanup(){
    	parent::cleanup();
    	unset($this->row);
    }
}


/**
* Template List Form class
*
* This class creates a list view based on a query
* Displaying the fields is done by TFields show methods
* So any special fields will show up as intended
*
*/
class TTemplateDBList extends TDBList {
    /**
    * Holds the current template object
    */
    var $template;

    /**
    * TTemplateDBList
    * Constructor
    *
    * Just initialize variables;
    */
    function TTemplateDBList(&$themodule,$form_name,$thequery,$thetitle="List",$thedetailform="",$theforeignkey="",$theattributes='',$the_parent_form_name="",$thekeyfield="id"){
        global $theme;
        $this->TDBList(&$themodule,$form_name,$thequery,$thetitle,$thedetailform,$theforeignkey,$theattributes,$the_parent_form_name,$thekeyfield);    
        $filename=firstExistingFile("themes/$theme/mod_{$this->modulename}/$form_name.html","themes/$theme/mod/{$this->modulename}/templates/forms/$form_name.html","mod/{$this->modulename}/templates/$form_name.html");
        $this->template =& new TTemplate($this,$filename);
    }

    /**
    * Run the query and show the results
    *
    * This function does all the work
    * - Run query as normal
    * - Create a header using the TField object
    * - Call TField objects to show results
    *
    */
    function on_Show() {    
        $modulename=$this->module->name;
        $query=$this->query;
        $query=str_replace("FOREIGN_KEY",$this->foreign_key_value,$query);
        $result=runquery($query);

        /**
        * Build header
        */
        $this->template->clearContent();

        $this->row = mysql_fetch_array($result);
        if ($this->row) {
            $this->template->processTag("header","show");

            // build content rows

            do {
            	foreach ($this->fields as $fieldname => $field) {if ($field->dbname<>"") { $this->fields[$fieldname]->setValue($this->row[$field->dbname]); } };
                $this->template->processTag("body","show");
            } while ($this->row = mysql_fetch_array($result));
            $this->template->processTag("footer","show");
            
            $this->content = $this->template->getContent();
        }
        else {
            $this->content = "<p>No records</p>";            
        }
        $this->template->clearContent(); // prevent it to use space in session store
        $detailform=$this->detailform;
        $module=$this->module->name;
        $foreignkey=$this->foreign_key_name;
        if (($this->hasAccess("w")) & ($this->hasAttribute('a'))){
            $this->actionbar->fields['addnew']->setLink("index.php?module=$module&amp;" . $module . "_op=form&amp;form_name=$detailform&amp;form_action=edit&amp;form_new=true&amp;foreign_key_name=$this->foreign_key_name&amp;foreign_key_value=$this->foreign_key_value");
            $this->actionbar->fields['addnew']->setVisible(TRUE);
        }
        else {
          if ($this->hasAttribute('a')) {$this->actionbar->fields['addnew']->setVisible(FALSE);}
        }
        $this->actionbar_content=$this->getActionBar();
    }
    
    /**
    * Just call On_show - a List form cannot be edited
    * @see On_show
    */
    function on_Edit() {
        $this->on_Show();
  }
}

/**
* This is a simple class that just shows the content of $content
*
* When the s attribute is set the form shows permanently
*/
class TStaticForm extends TForm {
    /**
    * Holds the static content of this form so it survives...
    */
    var $staticcontent;
    
    /**
    * Holds the template (if any)
    */
    var $template;
    
    /**
    * Constructor
    */
    function TStaticForm(&$themodule,$thename,$thetitle,$thecontent,$theattributes="s",$the_parent_form_name=""){
        $this->TForm($themodule,$thename,$thetitle,$theattributes,$the_parent_form_name);
        $this->staticcontent=$thecontent;
        
        $form_name=$this->name;
        $themename=$GLOBALS['theme'];
        $filename=firstExistingFile("themes/$themename/mod_{$this->modulename}/{$form_name}.html","themes/$themename/mod/{$this->modulename}/templates/forms/{$form_name}.html","mod/{$this->modulename}/templates/{$form_name}.html");
        if ($filename){

        	$this->template =& new TTemplate($this,$filename);
        }

    }
    function on_Show(){
        if ($this->content==""){
            $this->content=$this->staticcontent;
        }
        if ($this->template){
	        $this->template->clearContent();
	        $this->template->processTag("header","show");
	        $this->template->processTag("body","show");
	        $this->template->processTag("editcontrols","show");
	        $this->template->processTag("footer","show");
	        $this->content = $this->template->getContent();
	        $this->template->clearContent(); // prevent it to use space in session store

        }
    }
    function on_Edit(){
        $this->on_Show();
    }
}


/**
* Tree List Class
* 
* This class provides a tree view of a database table in which each element has
* a parent_id which points to their parent's key index (or NULL for root)
*
*/
class TTreeDBList extends TDBList{
    /**
    * An asscociative array holding the tree
    * each node has a title, url and partent_id tag
    */
    var $treelist;
    /**
    * The name of the module to call whene a node gets clicked
    */
    var $target_module;
        /**
    * The name of the form to call whene a node gets clicked
    */
    var $target_form;
        /**
    * The name of the action to trigger whene a node gets clicked
    */
    var $target_action;
    /**
    * The name of the database field that holds the leaf title
    */
    var $leaf_title;
    /**
    * The name of the database field that holds the parent ID
    */
    var $parent_id;
    
    /**
    * Constructor for TTreeDBList
    *
    *
    * @param string $the_parent_id The name of the database field that holds the parent ID
    * @param string $the_leaf_title The name of the database field that holds the leaf title
    */
    function TTreeDBList(&$themodule,$form_name,$thequery,$thetitle="List",$thedetailform="",$theforeignkey="",$theattributes='',$thekeyfield="id",$the_parent_id='parent_id',$the_leaf_title='title') {
        $this->TDBList(&$themodule,$form_name,$thequery,$thetitle,$thedetailform,$theforeignkey,$theattributes,'',$thekeyfield);
        $this->target_module=$this->modulename;
        $this->target_form=$thedetailform;
        $this->target_action='show';
        $this->leaf_title=$the_leaf_title;
        $this->parent_id=$the_parent_id;
    }
    
    /**
    * Insert a node into the tree
    *
    * @param integer $node_id The ID (just a numeric identifier) of this node
    * @param integer depth An integere that keeps track of the recursion
    * depth - to allow us to bail out if we appear to have hit a loop
    */
    function InsertNode($node_id,$depth){
      static $container;
        $content="";

        $title= $this->treelist[$node_id]['title'];
        $url  =$this->treelist[$node_id]['url'];
        $parent_id  =$this->treelist[$node_id]['parent_id'];
        
        // The depth construct prevents endless loops.
        if ($depth<=30) {
          $GLOBALS['testcounter']++;
            $subcontent='';
            // Search for child nodes and insert them - this makes this process recursive
            foreach ($this->treelist as $the_node_id =>$the_node){
                if ($the_node['parent_id']==$node_id and $the_node_id<>0){
                    $subcontent .= $this->InsertNode($the_node_id,$depth+1);
                }    
            }
            // Put a container with a unique number around the child content
            if ($subcontent!="") {               
              $container++;
              $show=$this->treelist[$node_id]['show'];
              $content .= "<div id=\"wm-container-id-$container\" class=\"wm-container wm-level-$depth $show\">$subcontent</div>\n" ;
            }
        } else {
            $content .= "Tree cut - depth >30" ;
        }        
        
        // Don't do a container for node 0 as it's empty.
        if ($node_id==0){            
            $content ="\n" . $content;
        }else{ 
            // If there are child nodes, make a 'parent'
            if ($subcontent) { 
              $node_body="<a>$title</a>";	
							$haschildren=" wm-haschildren"; 
							$click=" onclick=\"menuClick(this,'wm-container-id-$container','$url')\"";} 
						else {
						  // Otherwise, an item 						  
              $node_body="<a href=\"$url\">$title</a>";	
						  $click=" onclick=\"itemClick('$url')\""; 
						  $haschildren=" wm-hasnochildren";
						};
              $show=$this->treelist[$node_id]['show'];
              // The outer <div> is necessary to circumvent a *very* obscure IE Javascript bug.
              $content ="  <div><div class=\"wm-item wm-level-$depth$haschildren $show-item\" onmouseover=\"Hover(this,$depth)\" onmouseout=\"unHover(this)\"$click><div class=\"wm-inner\">$node_body</div></div></div>\n" . $content; 
        }
        return $content;
    }
    
    /**
    * Run the query and show the tree
    *
    * - Run query as normal*
    *
    */
    function on_Show() {
        $query=$this->query;
        $query=str_replace("FOREIGN_KEY",$this->foreign_key_value,$query);
        $result=runquery($query);

        /**
        * Fetch all rows into the $treelist array 
        */
        $this->row = mysql_fetch_array($result);
        while ($this->row) {
            $node_id=$this->row[$this->keyfield];
            $this->treelist[$node_id]['title']=$this->row[$this->leaf_title];
            $this->treelist[$node_id]['parent_id']=$this->row[$this->parent_id];
            $url=formURL($this->target_module,$this->target_form,$this->target_action,$node_id,'r');
            $this->treelist[$node_id]['url']=$url;
            $this->treelist[$node_id]['show']='wm-closed';
            $this->row = mysql_fetch_array($result);
        }
        /*
        * The following piece of code makes sure the path in the tree to the currently selected
        * webpage is actually shown. It does this by finding the current page from $_REQUEST and
        * working it's way up in the tree, setting the 'show' field to 'wm-open' on it's path
        * The other nodes remain at 'wm-closed'
        */
        $pageno=$_REQUEST['webpage'];
        if($pageno) {
          $this->treelist[$pageno]['show']='wm-open';
          while ($pageno<>0) {
            $pageno=$this->treelist[$pageno]['parent_id'];
            $this->treelist[$pageno]['show']='wm-open';
          }            
        }
        $this->treelist[0]['show']='wm-open';

        $content .= $this->InsertNode(0,0);
        $this->content=$content;        
        $detailform=$this->detailform;
        $module=$this->module->name;
        if (($this->hasAccess("w")) & (strpos($this->attributes,'a')!==FALSE)){
            $this->actionbar->fields['addnew']->setLink("index.php?module=$module&amp;" . $module . "_op=form&amp;form_name=$detailform&amp;form_action=edit&amp;form_new=true&amp;foreign_key_name=$this->foreign_key_name&amp;foreign_key_value=$this->foreign_key_value");
            $this->actionbar->fields['addnew']->setVisible(TRUE);
        }
        else {
          if (strpos($this->attributes,'a')!==FALSE) {$this->actionbar->fields['addnew']->setVisible(FALSE);}
        }
        $this->actionbar_content=$this->getActionBar();
        $GLOBALS['content']['FLG_catviz']='1';
    }        
}

/**
* Registry class
*
*/

class TRegistry {
	/**
	* TRegistry constructor 
	*/	
	function TRegistry(){
	}
	
	function setValue($module,$section,$key,$value){
		if ($module){
			$result=runQuery("select module_id from mod_moduleman where module_name=\"$module\"");
			$row=mysql_fetch_array($result);
			$module_id=$row['module_id'];
		}else{
			$module_id='0';
		}
		runQuery("update registry set registry_value=\"$value\" where module_id =$module_id and registry_section=\"$section\" and registry_key=\"$key\""); 
	}
	function getValue($module,$section,$key){
		if ($module){
			$result=runQuery("select registry_value from registry left join mod_moduleman using (module_id) where module_name=\"$module\" and registry_section=\"$section\" and registry_key=\"$key\"");
		}else{
			$result=runQuery("select registry_value from registry where module_id=0 and registry_section=\"$section\" and registry_key=\"$key\"");
		}
		$row=mysql_fetch_array($result);
		if ($row){
			return $row['registry_value'];
		} else{
			trigger_error("getValue: Cannot find key in registry: $module/$section/$key");			
		}
	}
	function addKey($module,$section,$key,$value,$type,$title,$help,$choices){
		if ($module){
			$result=runQuery("select module_id from mod_moduleman where module_name=\"$module\"");
			$row=mysql_fetch_array($result);
			$module_id=$row['module_id'];
		} else {
			$module_id='0';
		}
		runQuery("insert registry set registry_value=\"$value\", module_id=$module_id, registry_section=\"$section\", registry_key=\"$key\",registry_type=\"$type\", registry_title=\"$title\", registry_choices=\"$choices\", registry_help=\"$help\""); 	
	}
	function getKeys($module,$section){
		if ($module){
			$result=runQuery("select registry_key from registry left join mod_moduleman using (module_id) where module_name=\"$module\" and registry_section=\"$section\"");
		} else {
			$result=runQuery("select registry_key from registry  where module_id=0 and registry_section=\"$section\"");
		}		
		while ($row=mysql_fetch_array($result)) {
			$keys[]=$row['registry_key'];
		}
		return $keys;
	}
	function getKeyInfo($module,$section,$key){
		if ($module){
			$result=runQuery("select registry_value, registry_key, registry_type, registry_title, registry_help, registry_choices from registry left join mod_moduleman using (module_id) where module_name=\"$module\" and registry_section=\"$section\" and registry_key=\"$key\"");
		}else{
			$result=runQuery("select registry_value, registry_key, registry_type, registry_title, registry_help, registry_choices from registry where module_id=0 and registry_section=\"$section\" and registry_key=\"$key\"");
		}
		$row=mysql_fetch_array($result);
		if ($row){
			return $row;
		} else{
			trigger_error("getKeyInfo: Cannot find key in registry: $module/$section/$key");			
		}
	}
}

class TRegistryForm extends TSimpleForm {
	var $registrymodule;
	var $registrysection;
	var $keys;	
	
	function TRegistryForm(&$themodule,$thename,$thetitle,$theregistrymodule,$theregistrysection,$theattributes="",$the_parent_form_name=""){
		$this->registrymodule=$theregistrymodule;
		$this->registrysection=$theregistrysection;
		TSimpleForm::TSimpleForm($themodule,$thename,$thetitle,$theattributes,$the_parent_form_name);
		$this->keys=$GLOBALS['registry']->getKeys($this->registrymodule,$this->registrysection);
		if ($this->keys) {
			foreach ($this->keys as $key){
				$keyinfo=$GLOBALS['registry']->getKeyInfo($this->registrymodule,$this->registrysection,$key);
				switch ($keyinfo['registry_type']){
					case 'date':
						$field= &new TDateField ($this, $key, $keyinfo['registry_title']);
					break;
					case 'boolean':
						$field= &new TBooleanField ($this, $key, $keyinfo['registry_title']);				
					break;
					case 'choice': 
					break;
					default:
						$field=&new TStringField($this,$key,$keyinfo['registry_title']);
					break;
					
				}			
				$this->addField($field);
				$this->row[$key]=$keyinfo['registry_value'];
			}	
		}	
	}
	
	/*
	* Fetch the current values from the registry and feed them into the fields before showing the edit form
	*/
	function on_Edit(){
		if ($this->keys){
			foreach ($this->keys as $key){
				$this->fields[$key]->setValue($GLOBALS['registry']->getValue($this->registrymodule,$this->registrysection,$key));
			}
		}
		parent::on_Edit();
	}
	
	/**
    * Submit a form - store values (in registry)
    *
    * This gets called after an on_Edit when OK or Cancel is clicked
    */
    function on_Submit() {
        /* "fix" the IE image button issue */
        $thebutton=getButton();
        // Determine if OK was clicked
        if ($thebutton=="OK" & $this->hasAccess('w')) {            
            foreach ($this->fields as $fieldname => $field) {
            	$dbfieldname=$field->dbname;
            	$fieldname=$field->name;    
            	if (($dbfieldname<>"") & !$this->fields[$fieldname]->hasAttribute('s')){
            		$value=$this->fields[$fieldname]->getValue();
	            	$GLOBALS['registry']->setValue($this->registrymodule,$this->registrysection,$dbfieldname,$value);                    
                }
            }
            $GLOBALS['content']['statusline']="[OK: The registry has been updated]";
        } else {            
            $GLOBALS['content']['statusline']="[Cancel: The action has been cancelled]";
        }
        
        if ($GLOBALS['req_rootform']==false) {
            // force using the foreign key values saved in the object by clearing the request vars
            $GLOBALS['req_foreign_key_name']="";
            $GLOBALS['req_foreign_key_value']='';
            $GLOBALS['req_form_new']="";
        }

		if ($this->jumplist[$thebutton]) {
        	$formid=$this->jumplist[$thebutton];
        } else {
        	$formid=$this->_parent_form_name;
        }
        return $this->decodeFormID($formid);    
    }	
		
}

/**
* A very simple menu Form 
* Menu items are added using the addMenuItem method
* The menu is directly generated, only the resulting html is kept
*/
class TMenuForm extends TForm {
	/**
	* The complete HTML content of this menu. Populated by addMenuItem
	*/
	var $html;
	
	/**
	* Constructor: just initialize form and items array
	*/
	function TMenuForm(&$themodule,$thename,$thetitle,$theattributes="",$the_parent_form_name=""){
		TForm::TForm($themodule,$thename,$thetitle,$theattributes,$the_parent_form_name);
		$this->menuitems=array();		
	}
	
	/**
	* Add a menu item. Content is directly generated
	*/
	function addMenuItem($module,$form,$action,$icon,$title,$subtitle){
		global $theme;
		if ($icon=="") { $icon="menu_item.png";}
		$imagefilename=firstExistingFile("themes/$theme/mod_$module/$icon","themes/$theme/mod/$module/img/$icon","mod/$module/img/$icon","themes/$theme/img/$icon","lib/img/$icon");
		$title=getString("{$this->module->name}:{$this->name}:$form.title",$title);
		$subtitle=getString("{$this->module->name}:{$this->name}.$form.subtitle",$subtitle);
		$this->html .= "<img src=\"$imagefilename\" align=\"middle\" hspace=\"10\" vspace=\"10\" alt=\"$title\" /><a href=\"index.php?module=$module&amp;{$module}_op=form&amp;form_name=$form&amp;form_action=$action\">$title</a>&nbsp;";
		$this->html .= "$subtitle <br />"; 		
	}
	
	/**
	* Show edit content. Simply return the content that was generated by the addMenuItem statements
	*/
	function on_Edit(){
		$this->content = $this->html;
	}	
}
?>
