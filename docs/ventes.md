# Documentation Ventes

## Perimetre

Le flux ventes actuel passe principalement par le module `documents` et les `reglements`.

Types de documents utilises:

- `DE` : devis
- `BC` : bon de commande
- `BL` : bon de livraison
- `FA` : facture
- `BR` : bon de retour
- `FR` : facture retour

## Tables concernees

- `f_docentete` : en-tete du document
- `f_docligne` : lignes articles du document
- `f_comptet` : client / tiers
- `f_transporteurs` : transport livraison
- `f_reglements` : paiements lies au document

## Mapping MVC

- Model principal: [Document.php](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/app/Models/Document.php)
- Lignes: [DocumentLine.php](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/app/Models/DocumentLine.php)
- Controller: [DocumentController.php](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/app/Http/Controllers/DocumentController.php)
- Paiements: [ReglementController.php](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/app/Http/Controllers/ReglementController.php)
- Views: [resources/views/documents](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/resources/views/documents), [resources/views/reglements](/c:/Users/Dell/Desktop/Laravel-project/SmartGess/resources/views/reglements)

## Ce qui est verifie

- creation et modification de documents via `DocumentController`
- calcul automatique de `do_total_ht`, `do_total_tva`, `do_total_ttc`
- duplication d'un document
- changement du statut expedition
- mise a jour du statut paiement a partir des reglements valides

## Exemple de flux

1. L'utilisateur cree un document depuis `/documents/create`
2. Le controller valide les lignes et calcule les totaux
3. `f_docentete` est insere
4. `f_docligne` est rempli pour chaque article
5. Un reglement peut ensuite etre ajoute depuis `/reglements/create`
6. Le statut `do_statut` passe a `non regle`, `partiellement regle` ou `regle`

## Limites actuelles

- le formulaire `documents/_form.blade.php` a un habillage tres oriente caisse/vente
- il n'y a pas encore de separation technique en routes `ventes.*`
- le stock n'est pas decremente automatiquement apres validation d'une facture ou d'un BL
