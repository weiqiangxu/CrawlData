<?php
/**
 * @author HzqGhost <admin@whiledo.com> QQ:313143468
 * @version 1.0.0
 *
*/
class LibFile{
	// 文件名称
    protected $FileName = '';
    // 文件目录部分
    protected $PathParts = null;


    function __construct($FileName = null)
    {
        if($FileName != null)
		{
			$this->filename = $FileName;
		}
    }
    

    public function Info(){
        if(is_null($this->PathParts)){
            $this->PathParts = pathinfo($this->filename);
        }
    }
    
    public function Crc32($FileName=''){
        if($FileName=='') $FileName = $this->filename;
        return strtoupper(dechex(crc32(file_get_contents($FileName))));
    }    
    public function Dir(){
        $this->Info();
        return $this->PathParts['dirname'];
    }
    
    public function BaseName(){
        $this->Info();
        return $this->PathParts['basename'];
    }
    public function ExtName(){
        $this->Info();
        return $this->PathParts['extension'];
    }
    
    public function PutData($data){
        if(LibDir::CreateDir($this->Dir())){
            file_put_contents($this->filename,$data);
        }
    }
    
    public function GetData($data){
        return @file_get_contents($this->filename);
    }

	/*
	<method>
		<name>WriteData(将数据写入文件)</name>
		<remark>将数据写入文件</remark>
		<exp.>WriteData('mylog.txt', 1, "I am a boy!")</exp.>
		<Parameter>
			<para name="FileName" type="string">文件名</para>
			<para name="Mode" type="int">0=>r+, 1=>w, 2=>w+, 3=>a, 4=>a+, 5=>x, 6=>x+ （键）</para>
			<para name="Data" type="string">数据</para>
		</Parameter>
		<return>
			成功：true	
			失败：false
		</return>
		<author>Soul 2013-3-25</author>
	</method>
	*/
	public function WriteData($FileName, $Mode, $Data)
	{	
		
		// "r"	只读方式打开，将文件指针指向文件头。
		// "r+"	读写方式打开，将文件指针指向文件头。
		// "w"	写入方式打开，将文件指针指向文件头并将文件大小截为零。如果文件不存在则尝试创建之。
		// "w+"	读写方式打开，将文件指针指向文件头并将文件大小截为零。如果文件不存在则尝试创建之。
		// "a"	写入方式打开，将文件指针指向文件末尾。如果文件不存在则尝试创建之。
		// "a+"	读写方式打开，将文件指针指向文件末尾。如果文件不存在则尝试创建之。
		$ModeArray =  array(0=>'r+', 1=>'w', 2=>'w+', 3=>'a', 4=>'a+', 5=>'x', 6=>'x+');
		// 校验模式合法
		if(array_key_exists($Mode, $ModeArray))
		{
			// 文件名
			$this->filename = $FileName;
			
			if(LibDir::CreateDir($this->Dir()))
			{
				$Handle = fopen($this->filename, $ModeArray[$Mode]);
				$Data = sprintf("%s\r\n", $Data);
				try
				{
					fwrite($Handle, $Data);
					fclose($Handle);
					return true;
				}
				catch (Exception $e)
				{
					fclose($Handle);
				}
			}
		}
		return false;
	}
}