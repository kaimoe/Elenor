<?php
	$error = false;
	$dberror = false;
	$registered = false;
	$errorString = $dberrorString = $username = $password = $email = "";
	
	if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"]))
	{
		if(empty($_POST["username"]))
		{
			$error = true;
			$errorString .= "<br />Enter a username.";
		}
		else
		{
			$username = strtolower($_POST["username"]);
		}
		
		if(empty($_POST["password"]))
		{
			$error = true;
			$errorString .= "<br />Enter a password.";
		}
		
		if(empty($_POST["email"]))
		{
			$error = true;
			$errorString .= "<br />Enter an e-mail.";
		}
		else
		{
			$email = strtolower($_POST["email"]);
			if(!filter_var($email, FILTER_VALIDATE_EMAIL)) 
			{
				//$error = true;
				$errorString .= "<br />Invalid e-mail format."; 
			}
		}
		
		// This requires you to register your site with google recaptcha thing
		if($_POST["g-recaptcha-response"])
		{
			require_once("captcha.php");
			
			$captchaResult = getCaptchaResult($_SERVER["REMOTE_ADDR"], $_POST["g-recaptcha-response"]);
			if($captchaResult == null || !$captchaResult)
			{
				$error = true;
				$errorString .= "<br />Invalid captcha.";
			}
		}
		else
		{
			// Comment to be able to skip the captcha
			//$error = true;
			$errorString .= "<br />Please complete the captcha.";
		}
		
		if($error == false)
		{
			require_once("mysql.php");
			$md5str = "%s_%u_salt";
			
			$username = strtolower(db_escape($_POST["username"]));
			$password = db_escape($_POST["password"]);
			$mail = db_escape($_POST["email"]);
			$regtime = time() / 3600;
			$md5password = md5(sprintf($md5str, $password, $regtime));
			
			$query = sprintf("SELECT * FROM account_data WHERE username='%s' OR email='%s'", $username, $email);
			$result = db_query($query);
			$rows = array();
			
			if($result == false)
			{
				$dberror = true;
				$dberrorString .= "<br />There was an error connecting to the database.";
				// echo db_error();
			}
			else
			{
				while ($row = mysqli_fetch_assoc($result)) 
				{
					$rows[] = $row;
				}
			}
			
			if($dberror == false && count($rows) > 0)
			{
				if(strcmp($rows[0]["username"], $username) == 0)
				{
					$error = true;
					$errorString .= "<br />Username already in use.";
				}
				else if(strcmp($rows[0]["email"], $email) == 0)
				{
					$error = true;
					$errorString .= "<br />E-mail already in use.";
				}
			}
			
			// remove false to skip the register for testing purposes
			if(!false && $error == false && $dberror == false)
			{
				$query = sprintf("INSERT INTO account_data (username, password, email, regtime, isactive) 
								VALUES ('%s','%s','%s','%u','%u')", $username, $md5password, $email, $regtime, 0);
				$result = db_query($query);
				if(false || $result == false)
				{
					$dberror = true;
					$dberrorString .= "<br />Registration of new account failed.";
					// echo db_error();
				}
				else
				{
					$registered = true;
				}
			}
		}
	}
?>

<!DOCTYPE HTML>
<html lang="en">

<head>
  <title>Register</title>
  <meta name="description" content="website description" />
  <meta name="keywords" content="website keywords, website keywords" />
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <link rel="stylesheet" type="text/css" href="style/style.css" />
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body>
  <div id="main">
    <div id="site_content">
      <div id="content">
		<?php
			if($_SERVER["REQUEST_METHOD"] == "POST" && $error == false && $dberror == false && $registered == true)
			{
				?>
				<h2>Thank you for registering</h2>
				<p>These are your account details</p>
				<p>Username: <?php echo $username; ?><br />E-mail: <?php echo $email ?></p>
				<br />
				<p>Please activate your account with the link sent to your e-mail address.</p>
				<p>Take me <a href="index.php">home</a></p>
				<?php
			}
			else
			{
				?>
				<h2>Register an account</h2>
				<p>Complete the following details. The captcha is disabled for this example</p>
				<?php
					if($dberror == true)
					{
						?>
						<p>Please contact the administrators with the following message:
						<?php echo $dberrorString; ?>
						</p>
						<?php
					}
					else if($error == true)
					{
						?>
						<p>Please solve the following errors:
						<?php echo $errorString; ?>
						</p>
						<?php
					}
			}
		?>
		<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="POST">
		<div class="form_settings">
			<p>
				<span>Username</span>
				<input type="text" name="username" title="username" placeholder="Enter your username" value="<?php echo $username;?>" />
			</p>
			<p>
				<span>E-mail</span>
				<input type="email" name="email" title="email" placeholder="Enter your e-mail" value="<?php echo $email;?>" />
			</p>
			<p>
				<span>Password</span>
				<input type="password" name="password" title="password" placeholder="Enter your new password" value="" />
			</p>
			<p>
				<span>Captcha</span>
				<span class="g-recaptcha" data-theme="dark" data-sitekey="6LeGvw4TAAAAAMNu6fDmg55XM37b8UdYfml08X9P"></span>
				<!-- Your public key in that field -->
			</p>
			<p>
				<span>&nbsp;</span>
				<input class="submit" type="submit" name="submit" title="submit" value="Register"/>
			</p>
		</div>
		</form>
      </div>
    </div>
  </div>
</body>
</html>