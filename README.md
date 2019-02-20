Need 
* Git
* docker-compose

### How to install

* Clone files
```code
	git clone https://github.com/OrmaJever/snovio_test.git
	cd ./snovio_test
```

* Create test mysql data directory
```code
	mkdir /var/lib/test_mysql
```
* Create and run docker container from docker-compose.yml
```code
	docker-compose up -d
```

Test webpage - localhost:888

DB admin - localhost:8888 (host=mysql:3307 login=root pass=123456)
