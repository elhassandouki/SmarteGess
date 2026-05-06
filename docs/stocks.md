# Documentation Stocks

## Perimetre

Le module stock suit les quantites par `article` et par `depot`.

## Tables concernees

- `f_depots` : depots
- `f_stock` : stock par article et depot
- `f_articles` : stock global article, seuil mini, suivi stock

## Mapping MVC

- Model depot: [Depot.php](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/app/Models/Depot.php)
- Model stock: [Stock.php](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/app/Models/Stock.php)
- Model article: [Article.php](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/app/Models/Article.php)
- Controllers: [DepotController.php](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/app/Http/Controllers/DepotController.php), [StockController.php](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/app/Http/Controllers/StockController.php)
- Views: [resources/views/depots](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/resources/views/depots), [resources/views/stocks](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/resources/views/stocks)

## Ce qui est verifie

- listing de stock avec filtres depot / recherche / stock faible
- ajustement manuel de `stock_reel` et `stock_reserve`
- synchronisation du champ `f_articles.ar_stock_actuel` lors d'un ajustement manuel
- valorisation stock via `stock_reel * ar_prix_achat`

## Mouvement de stock

Etat actuel:

- il n'existe pas de table dediee du type `mouvements_stock`
- il n'existe pas de controller dedie aux entrees/sorties de stock
- l'historique n'est pas journalise automatiquement depuis les documents

En pratique, le stock est aujourd'hui gere de deux manieres:

1. saisie du stock courant sur la fiche article
2. ajustement manuel depuis l'ecran `stocks`

## Points d'attention

- `f_stock.stock_reel` et `f_articles.ar_stock_actuel` peuvent diverger si les mises a jour ne passent pas par `StockController@adjust`
- le filtre `stock faible` se base sur `ar_stock_actuel <= ar_stock_min`
- la logique de decrement/increment automatique a la validation d'une vente, d'un retour ou d'un achat n'est pas encore implemente

## Evolution recommandee

Pour aller vers un vrai suivi de mouvements:

1. creer une table `f_stock_mouvements`
2. y stocker `article_id`, `depot_id`, `type_mouvement`, `quantite`, `source_type`, `source_id`, `user_id`
3. recalculer `f_stock` a partir de ces mouvements ou maintenir `f_stock` comme table d'agregat
4. brancher les documents de vente, retour et achat sur cette logique
