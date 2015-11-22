.PHONY: all clean install uninstall 

all: garaged

clean:
	rm -rf mysql_test i2c_test garaged
	rm -rf tmp/*.o

tmp/mysql_test.o: src/mysql_test.cpp
	gcc -c -o rmp/mysql_test.o src/mysql_test.cpp `mysql_config --cflags`
mysql_test: tmp/mysql_test.o
	gcc -o mysql_test tmp/mysql_test.o `mysql_config --libs`

tmp/i2c_test.o: src/i2c_test.cpp 
	gcc -c -o tmp/i2c_test.o src/i2c_test.cpp `mysql_config --cflags`
i2c_test: tmp/i2c_test.o
	gcc -o i2c_test tmp/i2c_test.o `mysql_config --libs`

tmp/garaged.o: src/garaged.cpp 
	gcc -c -o tmp/garaged.o src/garaged.cpp `mysql_config --cflags`
garaged: tmp/garaged.o
	gcc -o garaged tmp/garaged.o `mysql_config --libs`


install:
	cp garaged bin/
uninstall:
	rm -f bin/garaged
