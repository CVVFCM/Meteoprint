# meteoprint

## 🇫🇷 Français

**meteoprint** génère un bulletin météo clair et **imprimable** pour un lieu donné, pensé
pour la voile et la gestion de course.

- Recherche d'un lieu par autocomplétion (géocodage Nominatim).
- Bulletin **aujourd'hui et demain**, en deux colonnes, à 5 moments (nuit, matin, midi,
  après-midi, soir).
- Par créneau : **direction, vitesse et rafales de vent**, température et symbole météo.
- Données **Open-Meteo**, modèle **Arome HD** (~1,5 km, Météo-France).
- Récupération **asynchrone** (Messenger) ; mise à jour en direct via **Mercure/Turbo**.
- Impression optimisée : **A4 paysage**, centré, sans liens ni en-têtes du navigateur.

## 🇬🇧 English

**meteoprint** produces a clear, **print-ready** weather report for a given location, aimed
at sailing and race management.

- Location search with autocomplete (Nominatim geocoding).
- **Today and tomorrow** report, in two columns, at 5 times (night, morning, noon,
  afternoon, evening).
- Per slot: **wind direction, speed and gusts**, temperature and a weather icon.
- Data from **Open-Meteo**, **Arome HD** model (~1.5 km, Météo-France).
- **Asynchronous** fetch (Messenger); live page update via **Mercure/Turbo**.
- Print-optimized output: **A4 landscape**, centered, no links or browser headers.

## Getting started

Requirements: Docker and `make`. Everything runs in containers (FrankenPHP, PostgreSQL).

```bash
make run     # build images, start containers, create DB, install assets
```

Then open https://localhost (accept the local TLS certificate).

```bash
make cs      # fix code style (php-cs-fixer, twig-cs-fixer, eslint, stylelint)
make test    # run the PHPUnit test suite
make clean   # stop containers and remove data/volumes
```

Stack: Symfony 8.1 / PHP 8.5, PostgreSQL, FrankenPHP, Mercure, AssetMapper.
