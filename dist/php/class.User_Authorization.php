<?php
class User_Authorization
{
	private $username;
	private $password;
	private $firstName;
	private $lastName;
	private $tabNo;

	public function __construct($param){
		$this->username = $param['username'];
		$this->password = $param['password'];
		$this->firstName = $param['fname'];
		$this->lastName = $param['lname'];
		//$this->tabNo = $param['tabno'];
	}

	public function getUsername(){
		return $this->username;
	}

	public function getPassword(){
		return $this->password;
	}

	public function getFirstName(){
		return $this->firstName;
	}

	public function getLastName(){
		return $this->lastName;
	}

	public function getFullName(){
		return $this->firstName.' '.$this->lastName;
	}

	public function getTabNo(){
		return $this->tabNo;
	}

	public function genSignature(){
		return md5($this->username.$this->password/*.$this->tabNo*/);
	}

	public function isAuthorization(/*$signature*/){
		/*if (isset($signature) && !is_null($this->genSignature())) {
			if ($signature === $this->genSignature()) {
				return true;
			}
		}
		return false;*/

		if (isset($this)) {
			return true;
		}
		return false;
	}
}
?>