<?php

namespace crazedsanity;

/**
 * Description of template
 *
 * @author danf
 */
class Template {
	private $_contents;
	private $_name;
	private $_templates = array();
	private $_blockRows = array();
	private $_origin;
	private $_dir;
	private $recursionDepth=10;



	//-------------------------------------------------------------------------
	/**
	 * @param $file         Template file to use for contents (can be null)
	 * @param null $name    Name to use for this template
	 */
	public function __construct($file, $name=null) {
		$this->_origin = $file;
		if(!is_null($name)) {
			$this->_name = $name;
		}
		if(!is_null($file)) {
			if (file_exists($file)) {
				try {
					if (is_null($name)) {
						$bits = explode('/', $file);
						$this->_name = preg_replace('~\.tmpl~', '', array_pop($bits));
					}
					$this->_contents = $this->get_block_row_defs(file_get_contents($file));
					$this->_dir = dirname($file);
				} catch (Exception $ex) {
					throw new \InvalidArgumentException;
				}
			}
			else {
				throw new \InvalidArgumentException("file does not exist (". $file .")");
			}
		}
	}
	//-------------------------------------------------------------------------



	//-------------------------------------------------------------------------
	/**
	 * @param $newValue     How many times to recurse (default=10)
	 */
	public function set_recursionDepth($newValue) {
		if(is_numeric($newValue) && $newValue > 0) {
			$this->recursionDepth = $newValue;
		}
		else {
			throw new \InvalidArgumentException();
		}
	}
	//-------------------------------------------------------------------------



	//-------------------------------------------------------------------------
	/**
	 * @param $name                 Internal var to retrieve
	 * @return array|mixed|string   Value of internal var
	 */
	public function __get($name) {
		switch($name) {
			case 'name':
				return $this->_name;
		
			case 'templates':
				return $this->_templates;

			case 'blockRows':
				return $this->_blockRows;

			case 'contents':
				return $this->_contents;

			case 'dir':
				return $this->_dir;

			case 'origin':
				return $this->_origin;
			
			default:
				throw new \InvalidArgumentException;
		}
	}
	//-------------------------------------------------------------------------



	//-------------------------------------------------------------------------
	/**
	 * @param $value        Set internal contents to this value.
	 */
	public function setContents($value) {
		$this->_contents = $this->get_block_row_defs($value);
	}
	//-------------------------------------------------------------------------



	//-------------------------------------------------------------------------
	/**
	 * @param Template $template    Template object to add
	 * @param bool $render          If the template should be rendered (default=true)
	 */
	public function add(\crazedsanity\Template $template, $render=true) {
		foreach($template->templates as $name=>$content) {
			$this->_templates[$name] = $content;
		}

		$template->_contents = $this->get_block_row_defs($template->_contents);

		if($render === true) {
			$this->_templates[$template->name] = $template->render();
		}
		else {
			$this->_templates[$template->name] = $template->contents;
		}
	}
	//-------------------------------------------------------------------------



	//-------------------------------------------------------------------------
	/**
	 * @param $name             Name of template var
	 * @param null $value       Value (contents) of template
	 * @param bool $render      See $render argument for add()
	 */
	public function addVar($name, $value=null, $render=true) {
		$x = new Template(null, $name);
		$x->setContents($value);
		$this->add($x, $render);
	}
	//-------------------------------------------------------------------------



	//-------------------------------------------------------------------------
	/**
	 * @param bool $stripUndefinedVars      Removes undefined template vars
	 * @return mixed|string                 Rendered template
	 */
	public function render($stripUndefinedVars=true) {
		$numLoops = 0;
		$out = $this->_contents;

//		//handle block rows.
//		$out = $this->get_block_row_defs($out);
//		foreach($this->_templates as $k=>$v) {
//			$this->_templates[$k] = $this->get_block_row_defs($v);
//		}

		while (preg_match_all('~\{(\S{1,})\}~', $out, $tags) && $numLoops < $this->recursionDepth) {
			$out = cs_global::mini_parser($out, $this->_templates, '{', '}');
			$numLoops++;
		}

		if($stripUndefinedVars === true) {
			$out = preg_replace('/\{.\S+?\}/', '', $out);
		}

		return $out;
	}
	//-------------------------------------------------------------------------



	//-------------------------------------------------------------------------
	/**
	 * @param $templateContents
	 * @return array
	 * @throws \Exception
	 */
	protected function get_block_row_defs($templateContents) {
		//cast $retArr as an array, so it's clean.
		$retArr = array();

		//looks good to me.  Run the regex...
		$flags = PREG_PATTERN_ORDER;
		$reg = "/<!-- BEGIN (\S{1,}) -->/";
		preg_match_all($reg, $templateContents, $beginArr, $flags);
		$beginArr = $beginArr[1];

		$endReg = "/<!-- END (\S{1,}) -->/";
		preg_match_all($endReg, $templateContents, $endArr, $flags);
		$endArr = $endArr[1];

		$numIncomplete = 0;
		$nesting = "";

		//create a part of the array that shows any orphaned "BEGIN" statements (no matching "END"
		// statement), and orphaned "END" statements (no matching "BEGIN" statements)
		// NOTE::: by doing this, should easily be able to tell if the block rows were defined
		// properly or not.
		if(count($retArr['incomplete']['begin'] = array_diff($beginArr, $endArr)) > 0) {
			//I'm sure there's an easier way to do this, but my head hurts too much when
			// I try to do the magic.  Maybe I need to put another level in CodeMancer...
			foreach($retArr['incomplete']['begin'] as $num=>$val) {
				$nesting = cs_global::create_list($nesting, $val);
				unset($beginArr[$num]);
				$numIncomplete++;
			}
		}
		if(count($retArr['incomplete']['end'] = array_diff($endArr, $beginArr)) > 0) {
			//both of the below foreach's simply pulls undefined vars out of the
			// proper arrays, so I don't have to deal with them later.
			foreach($retArr['incomplete']['end'] as $num=>$val) {
				$nesting = cs_global::create_list($nesting, $val);
				unset($endArr[$num]);
				$numIncomplete++;
			}
		}

		if($numIncomplete > 0) {
			throw new \Exception("invalidly nested block rows: ". $nesting);
		}

		//YAY!!! we've got valid data!!!
		//reverse the order of the array, so when the ordered array
		// is looped through, all block rows can be pulled.
		foreach(array_reverse($beginArr) as $k=>$v) {
//			$tempRow = new Template(null, $k);
//			$tempRow->setContents($v);
//			$this->_blockRows[$k] = $tempRow;

//			$rowContents = $this->setBlockRow($templateContents, $v);
			$tempRow = new Template(null, $v);
			$tempRow->setContents($this->setBlockRow($templateContents, $v));
			$this->_blockRows[$v] = $tempRow;


//			//now strip that row out, replacing it with a template var.
//			$templateContents = preg_replace('~<!-- BEGIN '. $k .' -->.*<!-- END '. $k .' -->~', '{__BLOCKROW__'. $k .'}', $templateContents);

		}

		return($templateContents);
	}//end get_block_row_defs()
	//---------------------------------------------------------------------------------------------



	//---------------------------------------------------------------------------------------------
	public function setBlockRow(&$contents, $handle, $removeDefs=true) {
		$name = $handle;

		$reg = "/<!-- BEGIN $handle -->(.+){0,}<!-- END $handle -->/sU";
		preg_match_all($reg, $contents, $m);
		if(!is_array($m) || !isset($m[0][0]) ||  !is_string($m[0][0])) {
			throw new \Exception("could not find ". $handle ." in '". $contents ."'");
		} else {
			if($removeDefs) {
				$openHandle = "<!-- BEGIN $handle -->";
				$endHandle  = "<!-- END $handle -->";
				$m[0][0] = str_replace($openHandle, "", $m[0][0]);
				$m[0][0] = str_replace($endHandle, "", $m[0][0]);
			}

			$contents = preg_replace($reg, "{" . "$name}", $contents);
//			$this->templateVars[$contents] = $contents;
//			$this->templateRows[$name] = $m[0][0];
//			$this->add_template_var($name, "");
//			$retval = $m[0][0];
		}
		return($contents);
	}
	//---------------------------------------------------------------------------------------------



	//---------------------------------------------------------------------------------------------
	public function parseBlockRow($name, array $listOfVarToValue, $useTemplateVar=null) {
		if(isset($this->_blockRows[$name])) {
			if(is_null($useTemplateVar)) {
				$useTemplateVar = '__BLOCKROW__'. $name;
			}

			$final = "";
			foreach($listOfVarToValue as $row => $kvp) {
				if(is_array($kvp)) {
					$tmp = clone $this->_blockRows[$name];
					foreach($kvp as $var=>$value) {
						$tmp->addVar($var, $value);
					}
					$final .= $tmp->render();
				}
				else {
					throw new \InvalidArgumentException("malformed key value pair in row '". $row ."'");
				}
			}
		}
		else {
			throw new \InvalidArgumentException("block row '". $name ."' does not exist... ". cs_global::debug_print($this,0));
		}
	}
	//---------------------------------------------------------------------------------------------
}
