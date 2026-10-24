<?php
session_start();

// =====================
// ------- DATA --------
// =====================
const FICHIER_VINYLES = __DIR__ . "/data/vinyles.php";
const FICHIER_UTILISATEURS = __DIR__ . "/data/utilisateurs.php";

require __DIR__ . "/AltoRouter.php";

$router = new AltoRouter();
// =====================
// ------ ROUTES -------
// =====================
$router->map('GET', '/', 'accueil');
$router->map('GET', '/catalogue', 'catalogue');
$router->map('GET', '/vinyle/[i:id]', 'vinyle');
$router->map('GET', '/connexion', 'connexion');
$router->map('GET', '/inscription', 'inscription');
$router->map('GET', '/admin', 'admin');

$router->map('POST', '/register', 'register_user');
$router->map('POST', '/auth', 'authentification');
$router->map('GET', '/deconnexion', 'deconnexion');

$router->map('POST', '/panier/ajouter','panier_ajouter');
$router->map('GET', '/panier', 'panier');
$router->map('POST', '/panier/vider','panier_vider');

// -------------------------
$match = $router->match();
if ($match !== false && is_callable($match['target'])) {
    call_user_func_array($match['target'], $match['params']);
} else {
    http_response_code(404);
    erreur();
}

// =====================
// ----- FUNCTIONS -----
// =====================

function accueil() {
    $all_vinyles = require_once(__DIR__ . "/data/vinyles.php");
    foreach ($all_vinyles as $a_vinyle) {
        $total_stock += $a_vinyle['stock'] ;
    }
    require_once(__DIR__ . "/views/accueil.php");
}

// ---- CATALOG and ITEM ----
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

// ---- CONNEXION and DISCONNEXION ---
function connexion() {
    require_once(__DIR__ . "/views/connexion.php");
}

function deconnexion(){
    session_unset();
    require_once(__DIR__ . "/views/accueil.php");
}


// ---- REGISTER, LOG IN, LOG OUT ----
function authentification() {
    // ---- VARS ----
    $all_users = require_once(__DIR__ . "/data/utilisateurs.php");
    $email = $_POST['email'];
    $password = $_POST['password'];

    // ---- MATCHING LOGS CHECK ----
    foreach ($all_users as $a_user) {
        if ($a_user['email'] === $email && password_verify($password, $a_user['mot_de_passe'])) {
            
            $_SESSION['is_authenticated'] = true;
            $_SESSION['user_email'] = $a_user['email'];
            $_SESSION['user_nom'] = $a_user['nom'];
            $_SESSION['user_role'] = $a_user['role'];

            if ($a_user['role'] === 'admin') {
                header('Location: /admin');
                return;
            } else {
                header('Location: /catalogue');
                return;
            }
        }
        else {
            header('Location: /connexion?erreur=1');
            return;
        }
    }
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


// ---- SHOPPING CART ----
function panier_ajouter(){
    $_SESSION['panier'][] = [
    'id' => $_POST['id'],
    'quantite' => $_POST['quantite'],
    ];
    header('Location: /panier');
}


function panier() {
    // ---- VARS ----
    $all_vinyles = require_once(__DIR__ . "/data/vinyles.php");
    $shopping_cart = $_SESSION['panier'] ;
    $lignes = [] ;
    $total_price = 0 ;

    foreach ($shopping_cart as $item) {
        $id = $item['id'];
        $quantity = $item['quantite'];

        foreach ($all_vinyles as $a_vinyle) {
            if ($a_vinyle['id'] == $id) {
                $subtotal_price =  $a_vinyle['prix'] * $quantity ;
                $total_price += $subtotal_price ;

                $lignes[] = [
                'titre' => $a_vinyle['titre'],
                'artiste' => $a_vinyle['artiste'],
                'prix' => $a_vinyle['prix'],
                'quantite' => $quantity,
                'sous-total' => $subtotal_price,
                ];
            }
        }
    }

    $shipping_fees = 5 ;
    $free_shipping_threshold = 60 ;
    $missing_for_free_shipping = $free_shipping_threshold - $total_price ;

    if ($total_price >= $free_shipping_threshold) {
        $shipping_fees = 0 ;
        $missing_for_free_shipping = 0 ;
    }
    
    $price_and_shipping = 0 ;
    if ($total_price > 0) {
        $price_and_shipping =  $total_price + $shipping_fees;
    }

    require_once(__DIR__ . "/views/panier.php");
}

function panier_vider() {
    unset($_SESSION['panier']);
    header('Location: /panier');
}

function admin(){
    require_once(__DIR__ . "/views/admin.php");
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
