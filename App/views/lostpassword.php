<h1>{pageTitle}</h1>

<?php include "messages.php"; ?>

<p>If you forgot your password, you can fill the form below, we will send an email so that you can change your password.</p>

<?php
if (! isset($post)) {
    $post = [];
}

$form = new \App\Form("lostpassword", $post);
$form->open("");
    $form->email("lostpassword_email", "Email: ");
    $form->submit("", "Request password change");
$form->close();
?>