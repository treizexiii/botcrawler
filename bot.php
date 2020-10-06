<?php

// site web à crawler
$url = 'http://www.dcplanet.fr';

// déclaration de la fonction de crawl
function crawl($url) {

  // initialisation de curl
  $ch = curl_init($url);

  // création d'un fichier texte pour stocker le contenu crawlé
  // effacement du fichier précédent si existe
  if(file_exists('fichier_html_brut')) {
    unlink('fichier_html_brut');
  }

  $fp_fichier_html_brut = fopen('fichier_html_brut', 'a');

  // définition des paramètres curl
  // 1 redirection de l'output dans le fichier txt
  curl_setopt($ch, CURLOPT_FILE, $fp_fichier_html_brut);

  // 2 on spécifie d'ignorer les headers HTTP
  curl_setopt($ch, CURLOPT_HEADER, 0);

  // exécution de curl
  curl_exec($ch);

  // fermeture de la session curl
  curl_close($ch);

  // fermeture du fichier texte
  fclose($fp_fichier_html_brut);

  // passage du contenu du fichier à une variable pour analyse
  $html_brut = file_get_contents('fichier_html_brut');

  // extraction des emails
  preg_match_all('#"mailto:.+"#U', $html_brut, $adresses_mail);

  // creation d'un fichier pour recevoir les mails
  $fp_fichier_emails = fopen('fichier_mails', 'a');

  // on créé une boucle pour placer tous les mails de la page dans le fichier
  foreach ($adresses_mail[0] as $element) {
    // on "nettoie" les mails en enlevant les guillemets et le "mailto:"
    // on passe donc de "mailto:addr@gmail.com" à addr@gmail.com
    $element = preg_replace('#"#', '', $element);
    $element = preg_replace('#mailto:#', '', $element);

    // on ajoute un retour chariot en fin de ligne pour avoir 1 mail/ligne
    $element .= "n";
    fputs($fp_fichier_emails, $element);
  }

  fclose($fp_fichier_emails);

  // extraction des liens
  preg_match_all('#"/?[a-zA-Z0-9_./-]+.(php|html|htm)"#', $html_brut, $liens_extraits);

  // si le fichier contenant les liens existe déjà
  if (file_exists('liens_a_suivre')) {
    // on l'ouvre
    $fp_fichier_liens = fopen('liens_a_suivre', 'a');

    // on créé une boucle pour enregistrer tous les liens ds le fichier
    foreach ($liens_extraits[0] as $element) {
      // on recharge le contenu dans la variable à chaque tour de boucle
      // pour être à jour si le lien est present +sieurs x sur la même page
      $gestion_doublons = file_get_contents('liens_a_suivre');

      // on enlève les "" qui entourent les liens
      $element = preg_replace('#"#', '', $element);
      $follow_url = $element;
      $follow_url .= "n";

      // creation d'un pattern pour la verification ds doublons
      $pattern = '#'.$follow_url.'#';

      // on verifie grace au pattern précédemment créé
      // que le lien qu'on vient de capturer n'est pas déjà ds le fichier
      if (!preg_match($pattern, $gestion_doublons)) {
          fputs($fp_fichier_liens, $follow_url);
      }
    }
  }

  // si le fichier contenant les liens n'existe pas
  else {
    // on le créé
    $fp_fichier_liens = fopen('liens_a_suivre', 'a');

    // puis on fait une boucle pour enregistrer tous les liens ds 1 fichier
    foreach ($liens_extraits[0] as $element) {
        $element = preg_replace('#"#', '', $element);
        $follow_url = $element;
        $follow_url .= "n";
        fputs($fp_fichier_liens, $follow_url);
    }
  }

  // fermeture du fichier contenant les liens
  fclose($fp_fichier_liens);
}

// on appelle une première fois la fonction avec l'url racine
crawl($url);


// ensuite on ouvre le fichier de liens pour visiter les autres pages du site
$lire_autres_pages = fopen('liens_a_suivre', 'r');

// on créé une boucle pour visiter chacun des liens
// on stop cette boucle quand le curseur arrive à la fin du fichier
$numero_de_ligne = 1;

while(!feof($lire_autres_pages)) {
  // curl ne comprend que les liens absolus
  // on formate donc nos liens relatifs en liens absolus
  $page_suivante = $url;
  $page_suivante .= fgets($lire_autres_pages);
  echo $numero_de_ligne . ' Analyse en cours, page : ' .  $page_suivante;
  $numero_de_ligne++;

  //on se contente de rappeler la fonction crawl avec nos nouveaux liens
  crawl($page_suivante);
}

fclose ($lire_autres_pages);
?>