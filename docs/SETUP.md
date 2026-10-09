# PathYatra setup for Windows and Linux

Updated: 2026-10-09. This guide runs the completed application locally with Docker. PHP, MariaDB, Node and Nginx are supplied by the containers; they do not need separate installation on the host.

## 1. Get the correct version

The completed and verified system is on **`ci/docker-browser-check`**. The default **`main`** branch still contains the older version, so its history can show an older date.

- [Completed project branch](https://github.com/S-am-ir/PathYatra-Refresh/tree/ci/docker-browser-check)
- [Commit history for that branch](https://github.com/S-am-ir/PathYatra-Refresh/commits/ci/docker-browser-check/)
- [Download that branch as a ZIP](https://github.com/S-am-ir/PathYatra-Refresh/archive/refs/heads/ci/docker-browser-check.zip)
- [Passing Docker/browser verification](https://github.com/S-am-ir/PathYatra-Refresh/actions/runs/37800791013)
- [Functional audit and limits](AUDIT.md)

Choose ZIP download or Git below. Git makes subsequent updates easier.

## 2. Install Docker

You need a supported Windows/Linux system, internet for the initial image/build downloads, available disk space, a browser, and permission to install the required software. Use the current system requirements on the official pages linked below.

If Docker is already installed and running, go to **Verify Docker**.

### Windows: Docker Desktop with WSL 2

Official links:

- [Docker Desktop for Windows: installer downloads and requirements](https://docs.docker.com/desktop/setup/install/windows-install/)
- [Microsoft WSL installation instructions](https://learn.microsoft.com/en-us/windows/wsl/install)
- [Docker's WSL 2 backend instructions](https://docs.docker.com/desktop/features/wsl/)
- [Git for Windows download](https://git-scm.com/install/windows) - optional when using ZIP

1. Check the Windows requirements on Docker's installer page. Hardware virtualization must be enabled in BIOS/UEFI. The documented WSL backend requires at least 8 GB RAM.
2. If WSL is not installed, open **PowerShell as Administrator**, run the following, and restart Windows when requested:

   ```powershell
   wsl --install
   ```

3. After restart, complete the Linux account setup if Windows opens the newly installed distribution. Update and check WSL:

   ```powershell
   wsl --update
   wsl --version
   wsl --list --verbose
   ```

   Docker documents WSL 2.1.5 or newer; install the current WSL release. If your distribution shows version 1, use Microsoft's linked instructions to convert it to WSL 2.

4. Download Docker Desktop from its official page, selecting the installer for your processor. Run it and select the **WSL 2 backend** when offered.
5. Start **Docker Desktop** from the Start menu, complete its initial prompts, and wait until the engine is running. PathYatra uses **Linux containers**.
6. Open a new normal PowerShell window for the remaining app commands. Docker Desktop must remain running.

If using an Ubuntu/other WSL terminal instead of Windows PowerShell, enable that distribution under Docker Desktop's **Settings → Resources → WSL Integration**. Keep the project and its commands in the same chosen environment.

### Linux: Docker Engine and the Compose plugin

Install the Engine plus the modern Compose plugin, used as **`docker compose`** with a space.

Official instructions for your distribution:

- [Ubuntu](https://docs.docker.com/engine/install/ubuntu/)
- [Debian](https://docs.docker.com/engine/install/debian/)
- [Fedora](https://docs.docker.com/engine/install/fedora/)
- [RHEL](https://docs.docker.com/engine/install/rhel/)
- [All supported Engine platforms](https://docs.docker.com/engine/install/)
- [Compose plugin installation](https://docs.docker.com/compose/install/linux/)
- [Git installation on Linux](https://git-scm.com/install/linux) - optional when using ZIP

For a **fresh Ubuntu installation** supported by Docker, the official repository method is:

```bash
sudo apt update
sudo apt install ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

sudo tee /etc/apt/sources.list.d/docker.sources <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF

sudo apt update
sudo apt install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo systemctl enable --now docker
sudo docker run --rm hello-world
```

If Docker/Podman/containerd is already installed, follow the distribution's official prerequisite/conflict instructions before replacing packages. Use the Debian/Fedora/RHEL guide for those systems rather than the Ubuntu commands.

Engine installations may require **`sudo`** before every Docker command in this guide, for example `sudo docker compose up --build -d`. To configure access without sudo, follow [Docker's Linux post-install instructions](https://docs.docker.com/engine/install/linux-postinstall/); Docker group membership grants administrative access to the host.

If you prefer a GUI, [Docker Desktop for Linux](https://docs.docker.com/desktop/setup/install/linux/) is an alternative and includes Compose. Follow its distribution-specific installer and VM requirements, then start Desktop before app commands. Use the selected installation's Docker context consistently.

### Verify Docker

In PowerShell or your Linux terminal:

```sh
docker --version
docker compose version
docker info
docker run --rm hello-world
```

On Linux Engine, prefix these with `sudo` if required. Successful `docker info` and `hello-world` confirm access to a running engine, beyond merely having a Docker command installed.

## 3. Download the project

### Option A: ZIP, with no Git installation

1. Download the [completed branch ZIP](https://github.com/S-am-ir/PathYatra-Refresh/archive/refs/heads/ci/docker-browser-check.zip).
2. On Windows, right-click it and choose **Extract All**. On Linux, extract it using the archive manager.
3. Open the extracted folder. The correct project root contains **`compose.yaml`**, **`frontend`**, **`api`** and **`database`**.
4. Open a terminal in that folder: on Windows, open the folder in File Explorer and use **Open in Terminal**; on Linux, use the file manager's terminal option or `cd` to its path.

The extracted folder's name can differ from `PathYatra-Refresh`; run the following app commands in the folder containing `compose.yaml`. A ZIP has no Git history and cannot use `git pull`.

### Option B: Git clone

Install Git using the official links above. On Ubuntu, Git can also be installed with `sudo apt install git`. Reopen your terminal after installing Git on Windows.

```sh
git clone --branch ci/docker-browser-check https://github.com/S-am-ir/PathYatra-Refresh.git
cd PathYatra-Refresh
git branch --show-current
git log -1 --date=iso --format=fuller
```

The branch command must show **`ci/docker-browser-check`**. The log shows your checkout's actual commit and timestamp.

If you already cloned the repository and are on `main`, save any local edits before switching:

```sh
git status
git fetch origin
git switch ci/docker-browser-check
git pull --ff-only origin ci/docker-browser-check
```

If Git reports conflicting local edits, commit or otherwise preserve them before switching; avoid discarding work just to update.

## 4. Start PathYatra

From the project root, with Docker running:

```sh
docker compose config --quiet
docker compose up --build --detach --wait --wait-timeout 300
docker compose ps --all
```

Use `sudo docker ...` on Linux Engine if needed. Initial downloads/building can take several minutes.

Startup builds the React interface and PHP image, creates MariaDB storage, runs schema migration/seed, and starts the API and web server. A fresh database contains **25 destinations and 104 activities**.

Expected status: **db, api and web** running/healthy; **init** exited with code **0** after its one-time migration command completes.

Open:

- **[http://localhost:5180](http://localhost:5180)** - the application
- **[http://localhost:5180/api/health.php](http://localhost:5180/api/health.php)** - API/database health JSON

The database/API have internal Docker ports; open the app's web port in your browser. Docker handles npm installation/builds and the PHP dependencies automatically.

### Demo accounts

| Role | Email | Password |
| --- | --- | --- |
| Admin | admin@yatra.com | Admin@123 |
| Traveler | traveler@yatra.com | Traveler@123 |

You can register another traveler. Demo credentials are inserted only when the matching email does not already exist. They are local demonstration accounts; set appropriate credentials/passwords before public hosting.

## 5. Configuration and a different port

Defaults work without a `.env` file. For a fresh checkout that needs custom settings, copy the template once:

**Windows PowerShell**

```powershell
Copy-Item .env.example .env
notepad .env
```

**Linux**

```bash
cp .env.example .env
```

Open `.env` in your editor. If it already exists, edit it rather than overwriting your configuration.

For port **5181**, use:

```dotenv
APP_PORT=5181
ALLOWED_ORIGINS=http://localhost:5181,http://127.0.0.1:5181
```

Keep the database password entries from the template, or choose your local passwords **before the first database initialization**. Preserve those settings on subsequent starts.

Apply changes:

```sh
docker compose up --build --detach --wait --wait-timeout 300
```

Open **http://localhost:5181**. Changing `DB_PASSWORD` or `DB_ROOT_PASSWORD` in the file after the database volume exists does not automatically change the accounts inside MariaDB; keep the original values or perform a deliberate database-password change.

## 6. Stop, restart and update

Stop while retaining the database:

```sh
docker compose down
```

Start again:

```sh
docker compose up --detach --wait --wait-timeout 300
```

For a Git checkout, get updates and rebuild from the project root:

```sh
git status
git switch ci/docker-browser-check
git pull --ff-only origin ci/docker-browser-check
docker compose up --build --detach --wait --wait-timeout 300
```

Preserve local edits/configuration before updating. For ZIP users, download the current branch ZIP again and preserve any custom `.env` configuration.

Users, catalog edits and saved trips live in a Docker volume. Normal rebuilds and `docker compose down` retain it. **`docker compose down -v` deletes this project's database volume**; use it only for an intentional reset of disposable data. Docker Desktop's volume-delete controls also remove stored data.

The migration runs during startup, supports existing project schemas and retains saved records. Docker's database is separate from a pre-existing native MySQL/MariaDB installation; bringing that database into Docker requires an explicit backup/import rather than merely starting the containers.

## 7. Location and testing from another device

Use **Use my current location** in the planner and allow the browser's location request. If the OS/browser cannot obtain location, use **Or choose a starting place**.

Location generally requires HTTPS or localhost. Another device using a plain HTTP LAN URL can open the app but may reject geolocation. For LAN access, use the host computer's LAN IP and selected port, such as `http://192.168.1.20:5180`, and permit the app through the host firewall on your trusted network. The devices must be able to reach each other.

Location controls destination ordering; travel from the origin to the first stop is not included in the schedule. Map tiles require access to OpenStreetMap.

## 8. Run the automated checks

For a **disposable test installation** with the expected demo accounts and seeded catalog:

```sh
docker compose --profile test run --rm test
```

This runs the planner and API/database tests inside Docker. It creates and cleans up temporary test records, so use a local/disposable database rather than a live deployment.

The final application revision passed **4,293 planner assertions, 56 API checks and five real Chromium journeys** on GitHub Actions. Browser tests additionally require Node and Playwright on the testing machine; those are optional for running or manually testing the app. Follow [VERIFICATION.md](VERIFICATION.md) for browser-test commands and the isolated Compose setup. Physical-device GPS and other browsers remain device checks.

Useful manual checks: registration/login; one/multiple destinations; location/manual origin; low/high budgets; transfer-day validation; map/PDF; save/reopen/delete; completion/reviews; admin catalog edits affecting new plans while preserving saved history.

## 9. Troubleshooting

| Symptom | What to check |
| --- | --- |
| Docker command not found | Finish Docker installation and open a new terminal |
| Cannot connect to Docker daemon / named pipe | Start Docker Desktop on Windows/Linux Desktop; on Linux Engine check `sudo systemctl status docker`; inspect `docker context ls` if using multiple engines |
| Linux permission denied for Docker socket | Use `sudo docker ...` or complete Docker's linked post-install access setup |
| WSL/virtualization error | Follow the Windows prerequisite instructions, update WSL, enable hardware virtualization and restart when requested |
| No configuration file found | Change to the project root containing `compose.yaml` |
| Compose command or --wait not recognized | Install/update the modern Compose plugin or Docker Desktop; use `docker compose`, not legacy `docker-compose` |
| Port already allocated / old app appears | Choose an unused `APP_PORT`, rebuild, and open that exact port; make sure you downloaded/switched to the completed branch |
| init exited with a nonzero code | Read `docker compose logs init db`; verify database configuration before retrying |
| MariaDB access denied after editing .env | Check the passwords used when the existing volume was first initialized; do not delete valuable data to bypass the error |
| Blank/error page or unhealthy service | Inspect `docker compose ps --all`, the health endpoint and logs below |
| Cannot obtain browser location | Check browser/OS permission and HTTPS/localhost, or choose a manual origin |
| Map background unavailable | Check network access to OpenStreetMap; markers/lines use the saved coordinates, while background tiles are external |
| Git pull cannot fast-forward | Preserve your local changes and inspect branch history; avoid resetting or overwriting work |

Collect diagnostics from the project root:

```sh
docker compose ps --all
docker compose logs --tail=100 init api web db
docker compose config --quiet
```

When reporting a problem, include your OS, branch/commit (or ZIP download date), browser, exact failing action, error text and relevant logs. Remove credentials from anything you share.
