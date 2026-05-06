# Documentation Achats

## Perimetre actuel

Le projet supporte deja une base `tiers + articles + documents + reglements`, mais il n'a pas encore un module achats dedie avec ses propres routes et vues.

Aujourd'hui, la partie achats repose surtout sur:

- les tiers de type `fournisseur`
- les articles avec `ar_prix_achat` et `ar_prix_revient`
- les documents generiques dans `f_docentete` / `f_docligne`
- les reglements fournisseurs

## Tables concernees

- `f_comptet`
- `f_articles`
- `f_docentete`
- `f_docligne`
- `f_reglements`

## Mapping MVC

- Tiers fournisseurs: [CompteTController.php](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/app/Http/Controllers/CompteTController.php)
- Articles: [ArticleController.php](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/app/Http/Controllers/ArticleController.php)
- Documents: [DocumentController.php](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/app/Http/Controllers/DocumentController.php)
- Reglements: [ReglementController.php](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/app/Http/Controllers/ReglementController.php)

## Ce qui existe deja

- creation de fournisseurs via `tiers`
- saisie du prix achat, prix de revient, TVA, unite, stock mini
- association d'un document a un tiers
- association d'un reglement a un document et a un tiers

## Ce qui manque pour un vrai module achats separe

- routes dediees du style `achats.index`, `achats.create`
- vues dediees `resources/views/achats/*`
- types de documents achats clairement nommes dans l'interface
- ecriture automatique de l'entree en stock apres reception achat
- journal ou historique des mouvements d'achat

## Recommendation

Si tu veux separer fonctionnellement `ventes` et `achats`, le plus propre sera:

1. garder `Document` comme modele commun
2. ajouter une couche de filtrage par nature metier (`vente` / `achat`)
3. creer des controllers et vues dedies par domaine
4. brancher les mouvements de stock en consequence
