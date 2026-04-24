# Praatdeurtje — Instructions for Claude

## What this project is

Praatdeurtje.nl is een WordPress website beheerd door Mylene.
Was voorheen gehost als subsite binnen het mycreatief.nl multisite-netwerk (blog_id 5).
Krijgt nu een eigen standalone WordPress-installatie op de server.

## Stack

| Layer | Tech |
|---|---|
| CMS | WordPress |
| Theme | Flatsome + praatdeurtje-child (nog aan te maken) |
| Hosting | SiteGround (Hostingpakket Advanced 1415) |

## Status

- Database: geëxtraheerd uit mycreatief multisite dump (blog_id 5, wp_5_ tabellen)
- Theme: Flatsome was gedeeld via mycreatief server — nog te installeren
- Server: eigen map op SiteGround aan te maken, los van mycreatief.nl

## Te doen bij opzet

1. WordPress installeren op SiteGround voor praatdeurtje.nl (eigen map)
2. Flatsome installeren + praatdeurtje-child aanmaken
3. Plugins installeren (CartFlows, WooCommerce, CMPLZ, EWWW, etc.)
4. Database importeren vanuit LOCAL naar live

## Local development

- LOCAL domain: praatdeurtje.local
- MySQL: 127.0.0.1:10029, DB=local, user=root, password=root

## Deploy

Code deploys via GitHub Actions op push naar `main`.
GitHub repo: https://github.com/MyCreatief/Praatdeurtje

## Handoff

Read `/.github/chat-handoff-log.md` at the start of every session.
Update it at the end with facts, decisions, open issues, and the next first step.
