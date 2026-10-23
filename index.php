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
$router->map('GET', '/connexion', 'login');
$router->map('GET', '/inscription', 'register');
$router->map('GET', '/panier', 'panier');
$router->map('GET', '/admin', 'admin');

$match = $router->match();
if ($match !== false && is_callable($match['target'])) {
    call_user_func_array($match['target'], $match['params']);
} else {
    http_response_code(404);
    erreur();
}

// ----- FUNCTIONS -----

function accueil() {
    require_once(__DIR__ . "/views/accueil.php");
}

function catalogue() {
    require_once(__DIR__ . "/views/catalogue.php");
}

// CAREFUL HERE OF INDEXATION DIFF BETWEEN TABLE AND PRDUCTS NAME
// CAREFUL HERE OF INDEXATION DIFF BETWEEN TABLE AND PRDUCTS NAME
// CAREFUL HERE OF INDEXATION DIFF BETWEEN TABLE AND PRDUCTS NAME
// CAREFUL HERE OF INDEXATION DIFF BETWEEN TABLE AND PRDUCTS NAME
// CAREFUL HERE OF INDEXATION DIFF BETWEEN TABLE AND PRDUCTS NAME
function vinyle($id) {
    $all_vinyles = require_once(__DIR__ . "/data/vinyles.php");
    $vinyle = $all_vinyles[$id];
    require_once(__DIR__ . "/views/vinyle.php");
}

function panier() {
    require_once(__DIR__ . "/views/panier.php");
}

function connexion() {
    require_once(__DIR__ . "/views/connexion.php");
}

function inscription(){
    require_once(__DIR__ . "/views/inscription.php");
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
