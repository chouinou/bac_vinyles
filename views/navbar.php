<div class="bandeau">Livraison offerte dès 60 € d'achat · Disques neufs et rééditions</div>
<header class="entete">
    <a class="logo" href="/">Le Bac<span>à</span>Vinyles</a>
    <nav class="nav">
        <a href="/">Accueil</a>
        <a href="/catalogue">Catalogue</a>
        <a href="/panier">Panier <span class="pastille">0</span></a>

        <?php if (isset($_SESSION['is_authenticated'])) :?>
            <span class="bonjour">Bonjour <?= $_SESSION['user_nom'] ?></span> 
            <?php if ($_SESSION['user_role'] == 'admin') :?>
                <a href="/admin" aria-current="page">Admin</a>
            <?php endif; ?>    
            <a href="/deconnexion" aria-current="page">Déconnexion</a>
        <?php else : ?>
            <a href="/connexion">Connexion</a>
            <a class="nav-cta" href="/inscription">Créer un compte</a>
        <?php endif; ?>
    </nav>
</header>
