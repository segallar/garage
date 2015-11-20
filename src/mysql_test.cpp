
// Заголовочные файлы
//#include <my_global.h>
#include <stdlib.h>
#include <stdio.h>
#include <mysql/mysql.h>

// Прототип функции обработки ошибок
void puterror( const char *);

// Главная функция программы1
int main(int argc, char *argv[])
{
  // Дескриптор соединения
  MYSQL conn;
  // Дескриптор результирующей таблицы
  MYSQL_RES *res;
  // Массив полей текущей строки
  MYSQL_ROW row;

  // Получаем дескриптор соединения
  if(!mysql_init(&conn))
     puterror("Error: can't create MySQL-descriptor\n");

  // Устанавливаем соединение с базой данных
  if(!mysql_real_connect(&conn,
                         "localhost",
                         "root",
                         "nigthfal",
                         "garage",
                         0,
                         NULL,
                         0))
     puterror("Error: can't connect to MySQL server\n");

  // Выполняем SQL-запрос
  if(mysql_query(&conn, "SELECT ts, temp FROM events ORDER BY id DESC LIMIT 2") != 0)
     puterror("Error: can't execute SQL-query\n");

  // Получаем дескриптор результирующей таблицы
  res = mysql_store_result(&conn);
  if(res == NULL)
      puterror("Error: can't get the result description\n");
  

  // Получаем первую строку из результирующей таблицы
  while (row = mysql_fetch_row(res)) {
  if(mysql_errno(&conn) > 0)
      puterror("Error: can't fetch result\n");

     // Выводим результат в стандартный поток
     fprintf(stdout, "ts: %s temp: %f\n", row[0],(float)row[1]);
  }

  // Освобождаем память, занятую результирующей таблицей
  mysql_free_result(res);

  // Закрываем соединение с сервером базы данных
  mysql_close(&conn);
}

void puterror( const char *str )
{
  fprintf(stderr, str);
  exit(1);
}
