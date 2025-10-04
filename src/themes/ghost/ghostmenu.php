<?php


	class GhostMenu extends btThemeMenu {
		
		public function __construct($sqlConnection) {
			
			parent::__construct("ghost", $sqlConnection);	
			
		}
		
		public function displayMenuCategory($loc="top") {
			
			$menuCatInfo = $this->menuCatObj->get_info();
			if($loc == "top") {
				
				echo $this->getHeaderCode($menuCatInfo);
				if($this->intMenuSection == 0 || $this->intMenuSection == 1) {
					echo "
						<div class='menuContentDiv'>
					";
				}
				
				
			}
			elseif($this->intMenuSection == 0 || $this->intMenuSection == 1) {
				echo "
					</div>
				";
			}
			
		}
		
		
		public function displayLoggedOut() {
			
			echo "
			
			<form action='".MAIN_ROOT."login.php' method='post' style='padding: 0px; margin: 0px'>
				<div class='loginTitle'></div>
				<div class='loginText_Username'></div>
				<div class='loginTextbox_Username'>
					<input type='text' name='user' class='loginTextbox'>
				</div>
				
				<div class='loginText_Password'></div>
				<div class='loginTextbox_Password'>
					<input type='password' name='pass' class='loginTextbox'>
				</div>
				
				<div class='rememberMeIMG loginText_RememberMe'></div>
				<div class='rememberMeCheckbox loginRememberMeBox' id='fakeRememberMe'></div>
				<input type='submit' name='submit' value='Login' id='btnLogin' style='display: none'>
				<div class='loginButton' id='btnFakeLogin'></div>
				
				<input type='hidden' value='0' id='rememberMe' name='rememberme'>
			</form>
			
			
			";
			
		}
		
		public function displayLoggedIn() {
			echo "
				<div class='loggedinTitle'></div>
				<div class='loggedinUserInfo'>
					<a href='".MAIN_ROOT."profile.php?mID=".$this->data['memberInfo']['member_id']."'>".$this->data['memberInfo']['username']."</a>
				</div>
				<div class='loggedinUserLinks'>
					<a href='".MAIN_ROOT."members'>MY ACCOUNT</a> - <a href='".MAIN_ROOT."members/console.php?cID=".$this->data['pmCID']."'>PM INBOX ".$this->data['pmCountDisp']."</a><span style='font-size: 5px'><br><br></span><a href='".MAIN_ROOT."members/signout.php'>SIGN OUT</a>
				</div>
			";
		}	
		
		
		
		
	}


?>