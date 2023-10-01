<?php
// session_destroy(); die();
session_start();

    // Si les données arrivent au serveur via la méthode POST
    if ( $_SERVER['REQUEST_METHOD'] === "POST" ) 
    {

        $postClean = [];
        $errors    = [];
        
        // Protéger le serveur contre les failles de type Xss
        foreach ($_POST as $key => $value) 
        {
            $postClean[$key] = htmlspecialchars(trim(addslashes($value)));
        }
        
        // Protéger le serveur contre les failles de type Csrf
        if ( 
            !isset($_SESSION['csrf_token']) || !isset($postClean['csrf_token']) ||
            empty($_SESSION['csrf_token'])  || empty($postClean['csrf_token']) ||
            $_SESSION['csrf_token'] !== $postClean['csrf_token']
         ) 
        {
            //  Effectuer un e redirection vers la page de laquelle proviennent les informations
            // Arrêter l'exécution du script
            return header("Location: $_SERVER[HTTP_REFERER]");
        }
        
        // Protéger le serveur contre les robots spameurs
        if ( !isset($postClean['honey_pot']) || !empty($postClean['honey_pot']) ) 
        {
            // Effectuer un e redirection vers la page de laquelle proviennent les informations
            // Arrêter l'exécution du script
            return header("Location: $_SERVER[HTTP_REFERER]");
        }

        // Mettre en place les contraintes de validation
        if (isset($postClean['name'])) 
        {
            if ( empty($postClean['name']) ) 
            {
                $errors['name'] = "Le nom du film est obligatoire.";
            }
            else if( mb_strlen($postClean['name']) > 255 )
            {
                $errors['name'] = "Le nom du film ne doit pas dépasser 255 caractères.";
            }
        }

        if (isset($postClean['actors'])) 
        {
            if ( empty($postClean['actors']) ) 
            {
                $errors['actors'] = "Le nom du/des acteurs est obligatoire.";
            }
            else if( mb_strlen($postClean['actors']) > 255 )
            {
                $errors['actors'] = "Le nom du/acteurs ne doit pas dépasser 255 caractères.";
            }
        }

        if (isset($postClean['review'])) 
        {
            if ( $postClean['review'] != "" ) 
            {
                if ( !is_numeric($postClean['review']) ) 
                {
                    $errors['review'] = "La note doit être un nombre.";
                }
                else if( $postClean['review'] < '0' || $postClean['review'] > '5' )
                {
                    $errors['review'] = "La note doit être comprise entre 0 et 5.";
                }
            }
        }


        if (isset($postClean['comment'])) 
        {
            if ( $postClean['comment'] != "" ) 
            {
                if( mb_strlen($postClean['actors']) > 1000 )
                {
                    $errors['comment'] = "Le commentaire ne doit pas dépasser 1000 caractères.";
                }
            }
        }
        
        // S'il y a des erreurs
        if ( count($errors) > 0 ) 
        {
            // Sauvegarder les messages d'erreur en session
            $_SESSION['form_errors'] = $errors;

            // Sauvegarder les anciennes données du formulaire en session
            $_SESSION['old'] = $postClean; 
            
            //  Effectuer un e redirection vers la page de laquelle proviennent les informations
            // Arrêter l'exécution du script
            return header("Location: $_SERVER[HTTP_REFERER]");

        }
        
            
            // Dans le cas contraire,
            
            // Arrondir la note à un chiffre après la virgule
            if ( isset($postClean['review']) && !empty($postClean['review']) ) 
            {
                $reviewRounded = round($postClean['review']);
            }
            
            // Etablir une connexion avec la base de données
            include __DIR__ . "/db/connexion.php";
            
            // Effectuer la requête d'insertion des données en base
            $req = $db->prepare("INSERT INTO film (name, actors, review, comment, created_at, updated_at) VALUES (:name, :actors, :review, :comment, now(), now() )");

            $req->bindValue(":name", $postClean['name']);
            $req->bindValue(":actors", $postClean['actors']);
            $req->bindValue(":review", $reviewRounded ? $reviewRounded : '');
            $req->bindValue(":comment", $postClean['comment']);

            $req->execute();
            
            // Générer le message flash de sussès de l'opération
            $_SESSION['success'] = "Le film a été ajouté à la liste avec succès.";

            //  Effectuer une redirection vers la page d'accueil
            // Arrêter l'exécution du script
            return header("Location: index.php");
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(30));

?>
<?php include __DIR__ . "/partials/head.php"; ?>

    <?php include __DIR__ . "/partials/nav.php"; ?>

    <main class="container">
        <h1 class="text-center display-5 my-3">Ajouter un film</h1>

        <div class="container">
            <div class="row">
                <div class="col-md-6 mx-auto">

                
                    <?php if( isset($_SESSION['form_errors']) && !empty($_SESSION['form_errors']) ) : ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul>
                                <?php foreach($_SESSION['form_errors'] as $error) : ?>
                                    <li>
                                        <?php echo $error ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </li>
                                <?php endforeach ?>
                            </ul>
                        </div>
                    <?php unset($_SESSION['form_errors']); ?>
                    <?php endif ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="name">Nom du film <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="<?php echo isset($_SESSION['old']['name']) ? $_SESSION['old']['name'] : "" ; unset($_SESSION['old']['name']);?>">
                        </div>
                        <div class="mb-3">
                            <label for="actors">Nom du/des acteurs <span class="text-danger">*</span></label>
                            <input type="text" name="actors" id="actors" class="form-control"  value="<?php echo isset($_SESSION['old']['actors']) ? $_SESSION['old']['actors'] : "" ; unset($_SESSION['old']['actors']);?>">
                        </div>
                        <div class="mb-3">
                            <label for="review">La note du film / 5</label>
                            <input type="number" step=".1" min="0" max="5" name="review" id="review" class="form-control" value="<?php echo isset($_SESSION['old']['review']) ? $_SESSION['old']['review'] : "" ; unset($_SESSION['old']['review']);?>">
                        </div>
                        <div class="mb-3">
                            <label for="comment">Les commentaires</label>
                            <textarea name="comment" id="comment" class="form-control" rows="4"><?php echo isset($_SESSION['old']['comment']) ? $_SESSION['old']['comment'] : "" ; unset($_SESSION['old']['comment']);?></textarea>
                        </div>
                        <div class="mb-3 d-none">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        </div>
                        <div class="mb-3 d-none">
                            <input type="hidden" name="honey_pot" value="">
                        </div>
                        <div class="mb-3">
                            <input formnovalidate type="submit" class="btn btn-primary">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>    

    <?php include __DIR__ . "/partials/footer.php"; ?>

<?php include __DIR__ . "/partials/foot.php"; ?>
