# Système Anti-Brute Force - AlloParents

## Vue d'ensemble

Ce système protège la page de connexion contre les attaques par force brute en limitant le nombre de tentatives de connexion échouées par adresse IP.

## Fonctionnalités

### Protection par IP
- **Limite**: 5 tentatives de connexion échouées maximum
- **Fenêtre de temps**: 5 minutes pour compter les tentatives
- **Durée de blocage**: 15 minutes après dépassement de la limite

### Interfaces utilisateur
- **Alerte de prévention**: Affichage du nombre de tentatives restantes
- **Blocage temporaire**: Désactivation du formulaire avec compte à rebours
- **Messages informatifs**: Feedback clair sur l'état du blocage

## Configuration

### Paramètres (modifiables dans `LoginRateLimiter.php`)

```php
private const MAX_ATTEMPTS = 5;        // Tentatives max avant blocage
private const LOCKOUT_DURATION = 900;  // Durée blocage (15 min)
private const WINDOW_DURATION = 300;   // Fenêtre de comptage (5 min)
```

### Détection d'IP
Le système vérifie plusieurs en-têtes pour obtenir la vraie IP :
- `HTTP_CF_CONNECTING_IP` (Cloudflare)
- `HTTP_X_FORWARDED_FOR` (Proxy standard)
- `HTTP_X_FORWARDED`
- `HTTP_X_CLUSTER_CLIENT_IP`
- `HTTP_FORWARDED_FOR`
- `HTTP_FORWARDED`
- `REMOTE_ADDR` (Fallback)

## Stockage

Utilise le cache Symfony (système de fichiers) pour stocker :
- **Tentatives échouées**: Timestamps des tentatives par IP
- **Blocages**: Temps de fin de blocage par IP

### Emplacement du cache
```
Site/var/cache/login_attempts/
```

## Intégration

### Services modifiés
1. **SecurityController**: Gestion des alertes et compteurs
2. **AppAuthenticator**: Vérification des blocages et reset des tentatives
3. **LoginRateLimiter**: Service principal de gestion

### Template mis à jour
- **Alertes visuelles**: Orange pour blocage, jaune pour avertissement
- **Formulaire désactivé**: Pendant les blocages
- **Compte à rebours**: Temps restant avant déblocage

## Utilisation

### Comportement normal
1. Connexion réussie → Reset des tentatives
2. Échec de connexion → Incrémentation du compteur
3. 5 échecs → Blocage de 15 minutes

### Messages d'état
- **1-4 tentatives**: "Il vous reste X tentative(s) avant le blocage"
- **5+ tentatives**: "Accès temporairement bloqué. Réessayez dans X minutes"

## Sécurité

### Avantages
- Protection contre les attaques par dictionnaire
- Limitation des tentatives automatisées
- Feedback utilisateur pour légitimes utilisateurs

### Considérations
- Utilise l'IP comme identifiant (peut affecter les utilisateurs derrière NAT)
- Cache en fichier (acceptable pour un usage modéré)
- Pas de notification admin (peut être ajoutée)

## Maintenance

### Nettoyage automatique
- Les tentatives expirent automatiquement après 5 minutes
- Les blocages se lèvent automatiquement après 15 minutes
- Le cache Symfony gère la suppression des anciennes entrées

### Monitoring
Pour surveiller les tentatives :
```bash
# Voir les fichiers de cache des tentatives
ls -la Site/var/cache/login_attempts/
```

## Extension possible

### Fonctionnalités futures
- Notification admin pour blocages répétés
- Liste blanche d'IPs de confiance
- Blocage progressif (durées croissantes)
- Intégration avec des services de géolocalisation
- Logs des tentatives suspectes
