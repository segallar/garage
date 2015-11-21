.PHONY: all clean 

all: mysql_test
	
clean:
			rm -rf mysql_test i2c_test  *.o

mysql_test.o: mysql_test.c
			gcc -c -o main.o main.c `mysql_config --cflags`
mysql_test: mysql_test.o
            gcc -o mysql_test main.o `mysql_config --libs`
    
i2c_test.o: i2c_test.c
			gcc -c -o i2c_test.o i2c_test.c
i2c_test: i2c_test.o
			gcc -o i2c_test i2c_test.o

