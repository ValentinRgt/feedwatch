# FeedWatch

[![Release workflow](https://img.shields.io/github/actions/workflow/status/ValentinRgt/feedwatch/release.yml?style=flat&logo=githubactions&logoColor=white&label=build)](https://github.com/ValentinRgt/feedwatch/actions/workflows/release.yml)
[![GitHub stars](https://img.shields.io/github/stars/ValentinRgt/feedwatch?style=flat&logo=github)](https://github.com/ValentinRgt/feedwatch/stargazers)
[![Latest release](https://img.shields.io/github/v/release/ValentinRgt/feedwatch?style=flat&logo=github)](https://github.com/ValentinRgt/feedwatch/releases/latest)
[![Open issues](https://img.shields.io/github/issues/ValentinRgt/feedwatch?style=flat&logo=github)](https://github.com/ValentinRgt/feedwatch/issues)
[![Last commit](https://img.shields.io/github/last-commit/ValentinRgt/feedwatch?style=flat&logo=github)](https://github.com/ValentinRgt/feedwatch/commits)
[![License](https://img.shields.io/github/license/ValentinRgt/feedwatch?style=flat)](LICENSE)

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-7.4-000000?style=flat&logo=symfony&logoColor=white)](https://symfony.com/)
[![Docker](https://img.shields.io/badge/ghcr.io-feedwatch-2496ED?style=flat&logo=docker&logoColor=white)](https://github.com/ValentinRgt/feedwatch/pkgs/container/feedwatch)
[![Docker Hub](https://img.shields.io/docker/v/valentinrgt/feedwatch?style=flat&logo=docker&logoColor=white&label=Docker%20Hub)](https://hub.docker.com/r/valentinrgt/feedwatch)
[![Docker Pulls](https://img.shields.io/docker/pulls/valentinrgt/feedwatch?style=flat&logo=docker&logoColor=white)](https://hub.docker.com/r/valentinrgt/feedwatch)
[![Docker Image Size](https://img.shields.io/docker/image-size/valentinrgt/feedwatch?style=flat&logo=docker&logoColor=white)](https://hub.docker.com/r/valentinrgt/feedwatch)

**FeedWatch** is an open-source, self-hosted technology monitoring tool. Aggregate RSS feeds, automate content collection, track trends, and centralize your technology monitoring with customizable sources.

## Getting Started

FeedWatch is distributed as a Docker image. To run the application:

```bash
docker run -d --name feedwatch \
  -p 80:80 \
  -p 443:443 \
  -v feedwatch_var:/app/var \
  valentinrgt/feedwatch:latest
```

> [!NOTE]
> The production image is based on [FrankenPHP](https://frankenphp.dev/), which is configured by default to redirect HTTP to HTTPS without taking custom port mappings into account. It is therefore recommended to access the application directly over HTTPS. If you remap the HTTPS port (e.g. `-p 8443:443`), make sure to use the explicit URL with the new port, such as `https://localhost:8443`. For any environment variable changes or further configuration, refer to the [FrankenPHP documentation](https://frankenphp.dev/docs/).

Once the container is running, create a user to be able to log in and manage categories and sources:

```bash
docker exec -it feedwatch php bin/console app:user:create
```

Additional user-management commands are available:

```bash
docker exec -it feedwatch php bin/console app:user:update <email:required>
docker exec -it feedwatch php bin/console app:user:delete <email:required>
```

You can then open the application in your browser and start adding categories and sources.

## Features

- **Source management** — Add, edit and remove the feeds you want to monitor. Two formats are supported: **XML/RSS** feeds and **HTML scraping** with custom XPath selectors (container, title, link, published-at) for sites without a feed. Each source has a configurable status (active/inactive) and fetch periodicity (from every 15 minutes to monthly).
- **Source monitoring** — Track source health with error detection, status monitoring, and historical fetch records to identify problematic or unreachable feeds.
- **Categories** — Organize your sources into categories and filter the feed accordingly.
- **Automatic feed collection** — A background scheduler fetches each source on its own schedule, with change detection (checksums) to skip unchanged feeds and avoid duplicate articles.
- **Aggregated feed** — Browse all collected articles in a single, paginated view, filterable by category.
- **Global search** — Search across articles, sources and categories from a single search bar available on the home and admin views.
- **Admin article management** — Review and remove collected articles directly from the admin panel.
- **Self-hosted** — Runs anywhere with Docker (FrankenPHP + SQLite), no external service required.
- **User authentication** — Registration, login and role-based access to the admin panel.

> Built with Symfony 7.4 (PHP 8.2+), Doctrine ORM, Twig, Tailwind CSS, Stimulus/Turbo (Hotwire), Symfony Scheduler and Symfony Messenger.

## Contributing

Contributions are welcome! FeedWatch is an open-source project and we'd love your help to make it better.

- **Found a bug or have an idea?** [Open an issue](../../issues) to report a problem or suggest a new feature.
- **Want to contribute code?** Fork the repository, create a branch for your change, and open a [pull request](../../pulls).

When contributing, please describe your changes clearly and make sure the project's quality tools (PHP-CS-Fixer, PHP CodeSniffer, PHPStan, PHPMD) and tests pass before submitting.

## License

FeedWatch is licensed under the **GNU General Public License v3.0 or later (GPL-3.0-or-later)**. See the [LICENSE](LICENSE) file for the full text.