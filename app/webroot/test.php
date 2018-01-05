
<?php


include("../Lib/PHPMailer/PHPMailerAutoload.php");

/*
 * AUTHOR :
 * $EMAIL : 보내는 사람 메일 주소
 * $NAME : 보내는 사람 이름
 * $SUBJECT : 메일 제목
 * $CONTENT : 메일 내용
 * $MAILTO : 받는 사람 메일 주소
 * $MAILTONAME : 받는 사람 이름
 */
function sendMail($EMAIL, $NAME, $SUBJECT, $CONTENT, $MAILTO, $MAILTONAME){

	$mail             = new PHPMailer();
	$body             = $CONTENT;

	$mail->IsSMTP(); // telling the class to use SMTP
	$mail->SMTPDebug  = 2;                      // enables SMTP debug information (for testing)
	// 1 = errors and messages
	// 2 = messages only
	$mail->CharSet    = "utf-8";
	$mail->SMTPAuth   = true;                   // enable SMTP authentication
//	$mail->SMTPSecure = "tls";                  // sets the prefix to the servier
//	$mail->Host       = "5.104.224.145";        // sets GMAIL as the SMTP server
//	$mail->Port       = 2525;                   // set the SMTP port for the GMAIL server
//	$mail->Username   = "iorderfoods@gmail.com";             // GMAIL username
//	$mail->Password   = "MAktA6AnQTkf7be";              // GMAIL password

	$mail->SMTPSecure = "ssl";                 // sets the prefix to the servier
	$mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
	$mail->Port       = 465;                   // set the SMTP port for the GMAIL server
	$mail->Username   = "iorderfoods@gmail.com";             // GMAIL username
	$mail->Password   = "Bcs12345";              // GMAIL password

	$mail->SetFrom($EMAIL, $NAME);
	$mail->AddReplyTo($EMAIL, $NAME);
	$mail->Subject    = $SUBJECT;
	$mail->MsgHTML($body);
	$address = $MAILTO;
	$mail->AddAddress($address, $MAILTONAME);
	if(!$mail->Send()) {
		echo "Mailer Error: " . $mail->ErrorInfo;
	} else {
		echo "Message sent!";
	}
	echo "-------------END\n";
}

echo "Mail Sender Start";
echo sendMail("iorderfoods@gmail.com", "CHULGIL-LEE", "TEST22", "CONTENTSTEST2222", "iorderfoods@gmail.com", "CHULGIL-LEE");
echo "End";

?>
