# Playwright testing

## Pre-requisites

### Install Docker

Install Docker Desktop if you don't have it installed:

-   [Docker Desktop for Mac](https://docs.docker.com/docker-for-mac/install/)
-   [Docker Desktop for Windows](https://docs.docker.com/docker-for-windows/install/)

### Install npm ( Node version v18.15.0 & npm v9.5.0 )

### Install playwright


## Folder structure

- All test should be written in the folder `tests/play/specs`
- All test files should be named as `{filename}.spec.js `
- Any utility code can be placed in `tests/play/utils` with appropriate naming. utils files can further be grouped as `admin`, `frontend` for better code management.

## Running tests on local

### Prep work for running tests

- Make sure Docker is installed and running. Use `docker ps` to confirm that the Docker containers are running. You should see a log indicating that everything had been built as expected

- `npm run play:up` (this will build the test site using Docker)
- `npm run play:run` (this will run tests in local)
- `npm run play:run:interactive` (this will run tests in interactive mode, opens up browser run all the test cases.)
- `npm run play:stop` (stops tests site in local)

### References

-   [Wordpress Environment Setup](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)
-   [Playwright Framework ](https://playwright.dev/)
