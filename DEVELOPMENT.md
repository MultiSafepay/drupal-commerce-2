## Requirements
- Docker and Docker Compose
- Expose token, follow instruction here: https://expose.beyondco.de/docs/introduction to get a token

## Installation
1. Clone the repository:
```
git clone https://github.com/MultiSafepay/drupal-commerce-2.git
``` 

2. Copy the example env file and make the required configuration changes in the .env file:
```
cp .env.example .env
```
- **EXPOSE_HOST** can be set to the expose server to connect to
- **APP_SUBDOMAIN** replace the `-xx` in `drupal-commerce-2-dev-xx` with a number for example `drupal-commerce-2-dev-05`
- **EXPOSE_TOKEN** must be filled in

3. In the ```docker-compose.yml``` file replace the name of the container with the name ```'your-url'``` with the ```APP_SUBDOMAIN``` and the ```EXPOSE_HOST```
4. In the ```docker-compose.yml``` in the expose container. within the ```entrypoint``` and the ```depends-on```. replace the ```your-url``` with the newly named container in the step above
5. (optional) if needed. fill in the GitHub token in the commented line in the ```Dockerfile``` 

6. Start the Docker containers
```
docker-compose up -d
```