# SmartGess

Application Laravel de gestion commerciale pour suivre:

- les tiers (`clients`, `fournisseurs`, `prospects`)
- les articles et familles
- les documents commerciaux (`devis`, `BC`, `BL`, `factures`, `retours`)
- les reglements
- le stock par depot

## Verification rapide

Verification faite le `2026-05-06` sur ce projet:

- `php artisan migrate:status` : toutes les migrations business sont `Ran`
- `php artisan route:list` : routes CRUD et metier disponibles
- `php artisan test` : `4 passed`

Un correctif a ete ajoute dans [app/Http/Controllers/DocumentController.php](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/app/Http/Controllers/DocumentController.php) pour accepter aussi l'ancien champ `do_type` et le convertir vers `type_document_code`. Ca garde la compatibilite avec l'ancien schema ou les anciens formulaires/tests.

## Mapping database / MVC

| Domaine | Tables | Models | Controllers | Views |
| --- | --- | --- | --- | --- |
| Familles | `f_familles` | `Family` | `FamilyController` | `resources/views/families/*` |
| Articles | `f_articles` | `Article` | `ArticleController` | `resources/views/articles/*` |
| Tiers | `f_comptet` | `CompteT` | `CompteTController` | `resources/views/tiers/*` |
| Transporteurs | `f_transporteurs` | `Transporteur` | `TransporteurController` | `resources/views/transporteurs/*` |
| Documents | `f_docentete`, `f_docligne` | `Document`, `DocumentLine` | `DocumentController` | `resources/views/documents/*` |
| Reglements | `f_reglements` | `Reglement` | `ReglementController` | `resources/views/reglements/*` |
| Depots | `f_depots` | `Depot` | `DepotController` | `resources/views/depots/*` |
| Stock | `f_stock` | `Stock` | `StockController` | `resources/views/stocks/*` |

## Documentation metier

- [Ventes](docs/ventes.md)
- [Achats](docs/achats.md)
- [Stocks](docs/stocks.md)

## Points d'attention

- Le module `documents` est aujourd'hui generic: il gere les types de documents mais ne separe pas encore des routes/views distinctes pour `ventes` et `achats`.
- Le module `stock` permet l'ajustement manuel par depot, mais il n'existe pas encore de table dediee `mouvements_stock` ni de journal automatique des entrees/sorties depuis les documents.
- `php artisan about --only=environment` remonte `Timezone: UTC`, alors que ton environnement de travail annonce `Africa/Casablanca`. Si tu veux un affichage date/heure local coherent, pense a verifier `APP_TIMEZONE`.
