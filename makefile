.PHONY: all clean 

all: i2c_test

clean:
	rm -rf mysql_test i2c_test *.o

mysql_test.o: src/mysql_test.cpp
	gcc -c -o mysql_test.o src/mysql_test.cpp `mysql_config --cflags`
mysql_test: mysql_test.o
	gcc -o mysql_test mysql_test.o `mysql_config --libs`

i2c_test.o: src/i2c_test.cpp 
	gcc -c -o i2c_test.o src/i2c_test.cpp `mysql_config --cflags`
i2c_test: i2c_test.o
	gcc -o i2c_test i2c_test.o `mysql_config --libs`
