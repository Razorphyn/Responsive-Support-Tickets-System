<?php
class Translator {
	
    private $language	= 'en';
    private $path		= 'php/';
	private $lang 		= array();
	
	public function __construct($language,$path){
		$this->language = $language;
		$this->path = $path;
	}
	
    private function findString($str) {
        if (array_key_exists($str, $this->lang[$this->language])) {
			return $this->lang[$this->language][$str];
        }
        else
			return $str;
    }
	
	public function __($str,$js) {
        if (!array_key_exists($this->language, $this->lang)) {
            if (file_exists($this->path.'translator/lang/'.$this->language.'.csv')){
				ini_set('auto_detect_line_endings',TRUE);
				$f=fopen($this->path.'translator/lang/'.$this->language.'.csv','r');
				while($line=fgetcsv($f)){
					
					$key=array_shift($line);
					$this->lang[$this->language][$key]=$line[0];
				}
				fclose($f);
				ini_set('auto_detect_line_endings',FALSE);
				if($js==false)
					echo $this->findString($str);
				else
					return $this->findString($str);
            }
            else {
				if($js==false)
					echo $str;
				else
					return $str;
            }
        }
        else {
			if($js==false)
				echo $this->findString($str);
			else
				return $this->findString($str);
        }
    }
}
?>