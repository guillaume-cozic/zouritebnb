# Ansible — provisioning du VPS

Installe **Docker Engine** + le plugin **Compose** sur le serveur de production,
prérequis du workflow `.github/workflows/deploy.yml` qui se connecte en SSH et
lance `docker compose -f docker-compose.prod.yml`.

## Ce que fait le playbook

Le rôle `docker` (Debian/Ubuntu) :

1. installe les prérequis (`ca-certificates`, `curl`, `gnupg`) ;
2. ajoute la clé GPG et le dépôt apt officiels de Docker ;
3. installe `docker-ce`, `docker-ce-cli`, `containerd.io`, `docker-buildx-plugin`,
   `docker-compose-plugin` ;
4. écrit `/etc/docker/daemon.json` (rotation des logs des conteneurs) ;
5. active et démarre le service `docker` ;
6. ajoute l'utilisateur de déploiement au groupe `docker` (docker sans `sudo`).

## Prérequis (poste local)

```bash
ansible --version        # ansible-core >= 2.12
# sinon : pipx install ansible-core   (ou pip install ansible)
```

Un accès SSH au serveur avec un utilisateur disposant de `sudo`.

## Configuration

```bash
cd ansible
cp inventory/hosts.ini.dist inventory/hosts.ini
$EDITOR inventory/hosts.ini      # IP/hostname, ansible_user, port, clé SSH
```

`inventory/hosts.ini` est gitignoré (il contient l'adresse du serveur).

## Lancer

```bash
cd ansible
ansible all -m ping              # vérifie la connexion SSH
ansible-playbook site.yml --check   # dry-run, aucune modification
ansible-playbook site.yml           # provisionne
```

Idempotent : relançable sans risque. Après le premier ajout au groupe `docker`,
ouvrir une nouvelle session SSH pour que l'appartenance au groupe prenne effet.

## Personnalisation

Les variables sont dans `roles/docker/defaults/main.yml` (surchargeables dans
l'inventaire) : liste des paquets, utilisateurs ajoutés au groupe `docker`,
config du démon.
