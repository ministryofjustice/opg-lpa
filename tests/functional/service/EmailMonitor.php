<?php

include '/mnt/test/config/email_config.php';

$runTimeSeconds = 6000;
$pauseSeconds = 6;

mainLoop($emailConfig, $runTimeSeconds, $pauseSeconds);

/**
 * Join lines that are broken by the equals sign in email body texts
 * 
 * @param string $str
 */
function stripEmailLineBreaks($str) 
{
    $str = str_replace("=\r\n", '', $str);
    
    return $str;
}

/**
 * Extract the plus part from emails of the form:
 * basename+pluspart@example.com
 * 
 * @param string $email
 */
function getPlusPartFromEmailAddress($email)
{
    $plusPos = strpos($email, '+');
    $atPos = strpos($email, '@');
    $userIdLength = $atPos - $plusPos - 1;
    $userId = substr($email, $plusPos + 1, $userIdLength);
    
    return $userId;
}

/**
 * Poll an IMAP inbox for OPG activation emails and write
 * a summary to the activation_emails direction
 * 
 * @param $inbox
 */
function monitorInbox($emailConfig)
{
    $inbox = getInbox($emailConfig);
    
    if ($inbox) {
        
        grabEmails(
            $inbox,
            'Activate your lasting power of attorney account',
            'activation',
            'signup\/confirm'
        );
        
        grabEmails(
            $inbox,
            'Password reset request',
            'passwordreset',
            'forgot-password\/reset'
        );

        imap_close($inbox);
    }
}


/**
 * @param inbox
 * @param subject
 * @param directory
 * @param overview
 * @param message
 * @param activationLink
 * @param toEmail
 * @param userId
 * @param contents
 */

function grabEmails($inbox, $subject, $type, $linkRegex)
{
    $emails = imap_search($inbox, 'SUBJECT "' . $subject . '"');
    
    if ($emails) {
         
        foreach($emails as $email_number) {
    
            $overview = imap_fetch_overview($inbox, $email_number, 0);
    
            $subject = $overview[0]->subject;
    
            $message = imap_fetchbody($inbox, $email_number, 1);

            $regex = '|(https:\/\/\S+' . $linkRegex . '\/[a-zA-Z0-9]+)|sim';

            $message = stripEmailLineBreaks($message);
            
            if (preg_match($regex, $message, $matches) > 0) {
                $activationLink = $matches[1];
   
                $toEmail = $overview[0]->to;
                $userId = getPlusPartFromEmailAddress($toEmail);
   
                $contents = $toEmail . ',' . $activationLink;
   
                file_put_contents('/mnt/test/activation_emails/' . $userId . '.' . $type, $contents);
                
                echo 'Found email for user ' . $userId . PHP_EOL;
   
                imap_delete($inbox, $overview[0]->msgno);
                
            } else {
                echo 'Message: "' . $subject . '" does not match regex ' . $regex . PHP_EOL;
                echo '----------------------------------------------------------------------------------';
                echo $message . PHP_EOL;
                echo '----------------------------------------------------------------------------------';
            }
        }
    }
}

/**
 * Get the inbox resource
 * 
 * @param string $emailConfig
 */
function getInbox($emailConfig)
{
    echo 'server:' .$emailConfig['server'];
    echo 'username:' .$emailConfig['username'];
    echo 'password:' .$emailConfig['password'];


    $inbox = imap_open(
        $emailConfig['server'],
        $emailConfig['username'],
        $emailConfig['password']
    );

    echo 'inbox' .$inbox;
    return $inbox;
}

/**
 * Expunge deleted emails
 */
function expungeInbox($emailConfig)
{
    $inbox = getInbox($emailConfig);
    
    imap_expunge($inbox);
}

/**
 * Poll the inbox for new emails
 */
function mainLoop($emailConfig, $runTimeSeconds, $pauseSeconds)
{
    while ($runTimeSeconds > 0) {
        monitorInbox($emailConfig);
        $runTimeSeconds -= $pauseSeconds;
        if ($runTimeSeconds > 0) {
            sleep($pauseSeconds);
            
            // By expunging here, any other running services will
            // have had time to grab the same emails before they
            // are permanently deleted
            expungeInbox($emailConfig);
        }
    };
}