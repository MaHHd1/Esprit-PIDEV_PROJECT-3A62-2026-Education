# Validations Côté Serveur - Documentation

## Vue d'ensemble
Toutes les validations sont effectuées **côté serveur** sans dépendre de HTML5 ou JavaScript. Les validations sont définies dans les Entities avec les Constraints Symfony Validator.

## Module (Entity: `src/Entity/Module.php`)

### Champs validés:

| Champ | Type | Validations |
|-------|------|-------------|
| `titreModule` | string(255) | NotBlank, Length(min:3, max:255) |
| `description` | text | Length(max:5000) |
| `ordreAffichage` | integer | NotNull, Range(0-9999) |
| `objectifsApprentissage` | text | Length(max:5000) |
| `dureeEstimeeHeures` | integer | Range(1-5000) |
| `statut` | string(20) | NotBlank, Choice(brouillon/publie/archive) |

## Cours (Entity: `src/Entity/Cours.php`)

### Champs validés:

| Champ | Type | Validations |
|-------|------|-------------|
| `codeCours` | string(50) | NotBlank, Length(3-50), Regex(alphanumeric + tirets), Unique |
| `titre` | string(255) | NotBlank, Length(3-255) |
| `description` | text | Length(max:5000) |
| `module` | ManyToOne | NotNull (obligatoire) |
| `niveau` | string(50) | Length(max:50) |
| `credits` | integer | Range(1-500) |
| `langue` | string(50) | Length(max:50) |
| `dateDebut` | date | - |
| `dateFin` | date | GreaterThanOrEqual(dateDebut) si dateFin fournie |
| `statut` | string(20) | NotBlank, Choice(brouillon/ouvert/ferme/archive) |

## Contenu (Entity: `src/Entity/Contenu.php`)

### Champs validés:

| Champ | Type | Validations |
|-------|------|-------------|
| `cours` | ManyToOne | NotNull (obligatoire) |
| `typeContenu` | string(50) | NotBlank, Choice(video/pdf/ppt/texte/quiz/lien) |
| `titre` | string(255) | NotBlank, Length(3-255) |
| `urlContenu` | string(255) | URL valide si fournie, NotBlank si type en (video/pdf/lien) |
| `description` | text | Length(max:5000) |
| `duree` | integer | Range(1-10000 minutes) |
| `ordreAffichage` | integer | NotNull, Range(0-9999) |
| `estPublic` | boolean | - |

## Comportement des Validations

### Lors de la création d'un cours/module/contenu:
1. L'utilisateur soumet le formulaire
2. Symfony valide les données contre les Constraints
3. Si validation échoue:
   - Les erreurs sont collectées et affichées dans le formulaire
   - L'entité n'est PAS persistée
   - L'utilisateur revient au formulaire avec les champs pré-remplis
4. Si validation réussit:
   - L'entité est persistée en base de données
   - Un message de succès est affiché

### Affichage des erreurs:
- **Erreurs globales**: Affichées en haut du formulaire dans une alerte Bootstrap
- **Erreurs par champ**: Affichées sous chaque champ avec icône et texte en rouge

## Exemple de flux de validation

### Créer un cours avec données invalides:
```
1. Code: "" (vide) → Erreur: "Le code du cours est obligatoire."
2. Titre: "AB" (trop court) → Erreur: "Le titre doit contenir au moins 3 caractères."
3. Module: null → Erreur: "Le module est obligatoire."
4. DateFin: 2025-01-01, DateDebut: 2025-12-31 → Erreur: "La date de fin doit être après..."
```

## Points clés

✅ **Sécurité**: Les validations côté serveur protègent TOUJOURS la base de données
✅ **Accessibilité**: Aucune dépendance à JavaScript - fonctionne avec requêtes HTTP simples
✅ **Performance**: Utilise le compilateur de constraints Symfony pour performances optimales
✅ **Maintenabilité**: Toutes les validations centralisées dans les Entities

## Tests

Pour tester les validations manuellement:
1. Allez sur `/admin/module/new` ou `/admin/cours/new`
2. Laissez les champs obligatoires vides
3. Cliquez sur "Enregistrer"
4. Les erreurs de validation s'affichent

Pour tester via CLI:
```bash
php bin/console lint:container
```

