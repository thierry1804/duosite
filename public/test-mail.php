<?php
// Script de test d'envoi d'email avec PHP natif

// Configuration
$to = 'thierry1804@gmail.com';
$subject = 'Test d\'envoi d\'email - ' . date('Y-m-d H:i:s');
$from = 'commercial@duoimport.mg';
$fromName = 'Duo Import MDG';

// En-têtes de l'email
$headers = 'From: ' . $fromName . ' <' . $from . '>' . "\r\n";
$headers .= 'Reply-To: ' . $from . "\r\n";
$headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";
$headers .= 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
$headers .= 'X-Priority: 1' . "\r\n";
$headers .= 'X-MSMail-Priority: High' . "\r\n";
$headers .= 'Importance: High' . "\r\n";

// Corps de l'email
$message = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test d\'envoi d\'email</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2c3e50;">Test d\'envoi d\'email</h1>
        <p>Ceci est un test d\'envoi d\'email depuis <strong>Duo Import MDG</strong> avec PHP natif.</p>
        <p>Date et heure: ' . date('Y-m-d H:i:s') . '</p>
        <p>Si vous recevez cet email, cela signifie que la configuration du serveur PHP pour l\'envoi d\'emails fonctionne correctement.</p>
        <p style="margin-top: 30px; font-size: 0.8em; color: #666;">
            © ' . date('Y') . ' Duo Import MDG. Tous droits réservés.
        </p>
    </div>
</body>
</html>
';

// Envoi de l'email
$success = mail($to, $subject, $message, $headers);

// Affichage du résultat
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test d\'envoi d\'email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        h1 {
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <h1>Test d\'envoi d\'email avec PHP natif</h1>';

if ($success) {
    echo '<div class="success">
        <h3>Succès!</h3>
        <p>L\'email de test a été envoyé avec succès à <strong>' . $to . '</strong>.</p>
        <p>Veuillez vérifier votre boîte de réception (et éventuellement le dossier spam) pour confirmer la réception.</p>
    </div>';
} else {
    echo '<div class="error">
        <h3>Erreur!</h3>
        <p>Une erreur s\'est produite lors de l\'envoi de l\'email de test.</p>
        <p>Vérifiez la configuration du serveur PHP pour l\'envoi d\'emails.</p>
    </div>';
}

echo '<div class="details">
        <h3>Détails techniques:</h3>
        <ul>
            <li><strong>De:</strong> ' . $fromName . ' &lt;' . $from . '&gt;</li>
            <li><strong>À:</strong> ' . $to . '</li>
            <li><strong>Sujet:</strong> ' . $subject . '</li>
            <li><strong>Date:</strong> ' . date('Y-m-d H:i:s') . '</li>
            <li><strong>Fonction mail() PHP:</strong> ' . ($success ? 'Succès' : 'Échec') . '</li>
        </ul>
    </div>
    
    <div class="info">
        <h3>Suggestions:</h3>
        <ul>
            <li>Vérifiez les logs du serveur pour plus de détails sur l\'erreur</li>
            <li>Vérifiez la configuration PHP pour l\'envoi d\'emails (php.ini)</li>
            <li>Assurez-vous que le serveur SMTP est correctement configuré et accessible</li>
            <li>Vérifiez les paramètres de sécurité du serveur SMTP</li>
        </ul>
    </div>
    
    <p><a href="test-mail.php">Réessayer</a> | <a href="/">Retour à l\'accueil</a></p>
</body>
</html>'; 