# Finance & Gestion Pro 💼

> Application web de gestion financière pour les petites entreprises en Afrique Centrale

---

## 📋 Table des matières

- [Présentation](#présentation)
- [Fonctionnalités](#fonctionnalités)
- [Technologies utilisées](#technologies-utilisées)
- [Installation](#installation)
- [Structure du projet](#structure-du-projet)
- [Base de données](#base-de-données)
- [Authentification](#authentification)
- [Abonnements](#abonnements)
- [Interface Expert](#interface-expert)
- [KYC](#kyc)
- [Sécurité](#sécurité)
- [Contact](#contact)

---

## 🎯 Présentation

Finance & Gestion Pro est une application web permettant aux petites entreprises congolaises de gérer leurs ventes, dépenses et stocks depuis leur téléphone.

---

## ✅ Fonctionnalités

- 🔐 Authentification sécurisée
- 📊 Dashboard avec statistiques
- 💰 Gestion des ventes
- 💸 Gestion des dépenses
- 📦 Gestion des stocks
- 👑 Interface Expert (Premium)
- 💳 Paiement MTN / Airtel Money
- 🪪 Vérification KYC
- 📱 Compatible mobile

---

## 🛠️ Technologies utilisées

| Technologie | Utilisation |
|---|---|
| PHP 8 | Backend |
| MySQL / MariaDB | Base de données |
| HTML / CSS | Frontend |
| JavaScript | Interactions |
| Google reCAPTCHA | Sécurité |
| InfinityFree | Hébergement |

---

## ⚙️ Installation
## Prérequis
- PHP 8+
- MySQL / MariaDB
- Serveur Apache

### Étapes

**1. Clone le projet**
```bash
git clone https://github.com/tonnom/finance-gestion
```

**2. Crée la base de données**
```bash
mysql -u root -p < database.sql
```

**3. Configure config.php**
```php
$host   = 'localhost';
$dbname = 'finance_gestion';
$user   = 'root';
$pass   = 'ton_mot_de_passe';
```

**4. Configure les clés reCAPTCHA**
```php
$siteKey   = 'TA_SITE_KEY';
$secretKey = 'TA_SECRET_KEY';
```

**5. Lance le serveur**
```bash
php -S localhost:8080
```

---

## 📁 Structure du projet

```
finance-gestion/
│
├── auth.php                 → Connexion / Inscription
├── dashboard.php            → Tableau de bord
├── ventes.php               → Gestion ventes
├── depenses.php             → Gestion dépenses
├── inventaire.php           → Gestion stocks
├── abonnements.php          → Plans abonnement
├── paiement.php             → Paiement MTN/Airtel
├── kyc.php                  → Vérification identité
├── config.php               → Configuration BDD
├── check_trial.php          → Vérification essai
│
├── CSS/                     → Styles
── JS/                      → Scripts
├── images/                  → Images
│
└── admin/
    ├── admin_dashboard.php
    ├── admin_validation.php
    └── kyc.php
```

---

## 🗄️ Base de données

### Tables principales

| Table | Description |
|---|---|
| utilisateurs | Comptes utilisateurs |
| ventes | Enregistrement ventes |
| depenses | Enregistrement dépenses |
| inventaire | Stock produits |
| demandes_abonnement | Abonnements |
| kyc | Vérification identité |

---

## 🔐 Authentification

- Connexion par email ou téléphone
- Mot de passe hashé avec PASSWORD_BCRYPT
- Protection reCAPTCHA v2
- Session PHP sécurisée
- Vérification KYC obligatoire

---

## 💳 Abonnements

| Plan | Prix | Durée |
|---|---|---|
| Essai | Gratuit | 3 jours |
| PME | 5 000 FCFA | 1 mois |
| Start-up | 10 000 FCFA | 1 mois |
| Entreprise | 15 000 FCFA | 1 mois |

### Paiement
- MTN Mobile Money : +242 061 714 780
- Airtel Money : +242 044 774 122
- Activation via admin 

---

## 👑 Interface Expert

Fonctionnalités débloquées :
- Graphiques financiers
- Analyse hebdomadaire
- Courbe de croissance
- Rapports avancés

---

## 🪪 KYC

### Documents requis
- Pièce d'identité (recto/verso)
- Selfie avec pièce d'identité

### Statuts

| Statut | Description |
|---|---|
| non_soumis | Pas encore soumis |
| en_attente | En cours de vérification |
| verifie | Approuvé ✅ |
| rejete | Rejeté ❌ |

---

## 🔒 Sécurité

- Requêtes PDO préparées (anti SQL injection)
- htmlspecialchars() sur toutes les données
- Vérification propriétaire sur chaque requête
- Headers anti-cache sur pages admin
- reCAPTCHA sur formulaires
- Antivirus manuel sur uploads
- Vérification Magic Bytes des fichiers

---

## 📞 Contact

| | |
|---|---|
| Développeur | KIMINOU Degrace |
| Téléphone | +242 061 714 780 |
| WhatsApp | +242 061 714 780 |
| Ville | Brazzaville, Congo |

---

© 2026 Finance & Gestion Pro — KIMINOU Degrace

