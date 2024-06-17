<?php
// Définir l'URL de base de votre application
$base_url = 'http://localhost/msn-connect';

if (!empty($_SESSION['id_utilisateur'])) {
    header('Location: /');
    exit;
}

$message = "";

if (isset($_POST['reset_password'])) {
    $email = $_POST['email'];

    // Assurez-vous d'avoir une connexion à la base de données dans $dbh
    $requete = $dbh->prepare("SELECT * FROM utilisateur WHERE email = ?");
    $requete->execute([$email]);
    $user = $requete->fetch();

    if ($user) {
        // Générer un token unique
        $token = bin2hex(random_bytes(32));
        $expires = date("U") + 3600; // Le token expire dans 1 heure

        // Supprimer les anciens tokens pour cet utilisateur
        $dbh->prepare("DELETE FROM password_reset WHERE email = ?")->execute([$email]);

        // Insérer le nouveau token dans la base de données
        $requete = $dbh->prepare("INSERT INTO password_reset (email, token, expires) VALUES (?, ?, ?)");
        $requete->execute([$email, $token, $expires]);

        // Créer le lien de réinitialisation
        $reset_link = "http://msn-connect/reset_password.php?token=" . $token . "&email=" . urlencode($email);

        // Préparer l'email
        $to = $email;
        $subject = "Réinitialisation de mot de passe";
        $subject_encoded = mb_encode_mimeheader($subject, 'UTF-8');

        $message_content = "Bonjour,\n\nPour réinitialiser votre mot de passe, veuillez cliquer sur le lien suivant :\n\n";
        $message_content .= $reset_link . "\n\n";
        $message_content .= "Ce lien expirera dans une heure.\n\nCordialement,\nMSN Connect";

        $headers = "From: cindy.singer@stagiairesmns.fr\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/plain; charset=UTF-8\r\n";

        if (mail($to, $subject_encoded, $message_content, $headers)) {
            $message = "Un e-mail de réinitialisation de mot de passe a été envoyé à votre adresse e-mail.";
        } else {
            $message = "Une erreur s'est produite lors de l'envoi de l'e-mail.";
        }
    } else {
        $message = "Aucun utilisateur trouvé avec cette adresse e-mail.";
    }
}
