Need 
* Git
* docker-compose

### How to install

* Clone files
```code
	git clone https://github.com/OrmaJever/snovio_test.git
	cd ./test
```

* Create test mysql data directory
```code
	mkdir /var/lib/test_mysql
```
* Create and run docker container from docker-compose.yml
```code
	docker-compose up -d
```

Test webpage - localhost:8888

DB admin - localhost:888 (host mysql:3307)
