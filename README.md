# Feverup Code Challenge — Pokémon CPT (WordPress + Docker)

This repository provides a **fully containerized WordPress environment** that implements a complete Pokémon management system as part of the Feverup Code Challenge.  
It includes a custom **Pokémon CPT plugin**, **PokéAPI importer**, **REST API routes**, **AJAX handler**, and **PHPUnit automated tests**.  

The environment is self-contained — once cloned, it can be launched and tested entirely via Docker.

---

## 🧩 Overview

This project includes:

- A custom **WordPress plugin** (`pokemon-cpt`) that:
  - Registers a `pokemon` Custom Post Type and `pokemon_type` taxonomy
  - Imports Pokémon data from the [PokéAPI](https://pokeapi.co)
  - Provides custom REST API endpoints under `/wp-json/pokemon/v1/`
  - Adds AJAX-powered front-end interaction
  - Implements admin import actions and CLI commands
- A Docker-based setup with **WordPress**, **MariaDB**, and **PHPUnit test environment**
- Preconfigured database (optional `data/db_data.sql`) with example Pokémon
- Automated **unit and integration tests** for CPT registration, taxonomy, and importer logic

---

## ⚙️ Requirements

- [Docker](https://www.docker.com/) and [Docker Compose](https://docs.docker.com/compose/)
- No additional local dependencies required

---

## 🚀 Quick Start

Clone the repository and start the full environment:

```bash
git clone https://github.com/YOUR-USERNAME/feverup.git
cd feverup
docker-compose up -d
