<?php
session_start();

// ----- DATA -----
const FICHIER_VINYLES = __DIR__ . "/data/vinyles.php";
const FICHIER_UTILISATEURS = __DIR__ . "/data/utilisateurs.php";

require __DIR__ . "/AltoRouter.php";

$router = new AltoRouter();

// ----- ROUTES -----

$router->map('GET', '/', 'accueil');
$router->map('GET', '/catalogue', 'catalogue');
$router->map('GET', '/vinyle/[i:id]', 'vinyle');
$router->map('GET', '/connexion', 'connexion');
$router->map('GET', '/inscription', 'inscription');
$router->map('GET', '/panier', 'panier');
$router->map('GET', '/admin', 'admin');

$router->map('POST', '/register', 'register_user');

$match = $router->match();
if ($match !== false && is_callable($match['target'])) {
    call_user_func_array($match['target'], $match['params']);
} else {
    http_response_code(404);
    erreur();
}

// ----- FUNCTIONS -----

function accueil() {
    $all_vinyles = require_once(__DIR__ . "/data/vinyles.php");
    foreach ($all_vinyles as $a_vinyle) {
        $total_stock += $a_vinyle['stock'] ;
    }
    require_once(__DIR__ . "/views/accueil.php");
}

function catalogue() {
    $all_vinyles = require_once(__DIR__ . "/data/vinyles.php");

    // null coalescing that attribute a '' value in case $genre or $recherche == null *   
    $genre = $_GET['genre'] ?? '' ;
    $recherche = $_GET['recherche'] ?? '' ;
    $resultats = [] ;
    print_r($recherche);

    stripos($a_vinyle['titre'] . " " . $a_vinyle['artiste'], $recherche) ;

    // LOOP FILTER
    foreach ($all_vinyles as $a_vinyle) {
        if (($a_vinyle['genre'] == $genre) 
            && (stripos($a_vinyle['titre'] . " " . $a_vinyle['artiste'], $recherche) != 0)) {
            $resultats[] = $a_vinyle ;
        }
    }
    if ((empty($resultats) == true) && ($genre == "") && ($recherche == "")) {
        $resultats = $all_vinyles ;
    }

    require_once(__DIR__ . "/views/catalogue.php");
}

function vinyle($id) {
    $all_vinyles = require_once(__DIR__ . "/data/vinyles.php");
    $the_vinyle = null ;
    foreach ($all_vinyles as $a_vinyle) {
        if ($a_vinyle['id'] == $id) {
            $the_vinyle = $a_vinyle;
            break;
        }
    }
    if ($the_vinyle == null) {
        http_response_code(404);
        erreur();
    }
    require_once(__DIR__ . "/views/vinyle.php");
}

function panier() {
    require_once(__DIR__ . "/views/panier.php");
}

function connexion() {
    require_once(__DIR__ . "/views/connexion.php");
}

function inscription() {
    require_once(__DIR__ . "/views/inscription.php");
}

function register_user(){
    // ---- VARS ----
    $all_users = require_once(__DIR__ . "/data/utilisateurs.php");
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmation = $_POST['confirmation'];

    // ---- ERROR CHECKS ----
    if (empty($name) || empty($email) || empty($password) || empty($confirmation)) {
        header('Location: /inscription?erreur=champs') ;
        return;
    }

    if (strlen($password) < 8) {
        header('Location: /inscription?erreur=mdp');
        return;
    }

    if ($password !== $confirmation) {
        header('Location: /inscription?erreur=confirmation') ;
        return;
    }

    foreach ($all_users as $a_user) {
        if ($email == $a_user['email']) {
            header('Location: /inscription?erreur=email') ;
            return;
        }
    }

    // ---- ADD USER ----
    $new_user = [
        'id' => count($all_users) + 1, 
        'nom' => $name,
        'email' => $email,
        'mot_de_passe' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'user',
        'date_inscription' => date('Y-m-d H:i:s')
    ];
    $all_users[] = $new_user;
    print_r($new_user);
    ecrireTable(FICHIER_UTILISATEURS, $all_users);
    
    header('Location: /connexion?inscrit=1');
}

function erreur() {
    require_once(__DIR__ . "/views/404.php");
}

// =====================================================================
//  OUTILS (fourni)
// =====================================================================

// Réécrit entièrement un fichier de data/ avec le tableau $lignes
// Exemple : ecrireTable(FICHIER_UTILISATEURS, $users);
function ecrireTable(string $fichier, array $lignes): void
{
    $contenu = "<?php\n\nreturn " . var_export(array_values($lignes), true) . ";\n";
    file_put_contents($fichier, $contenu, LOCK_EX);

    // PHP garde les fichiers en cache (OPcache) : on lui dit que celui-ci a changé,
    // sinon le prochain require_once pourrait relire l'ANCIENNE version pendant 2 secondes.
    if (function_exists('opcache_invalidate')) {
        opcache_invalidate($fichier, true);
    }
}
