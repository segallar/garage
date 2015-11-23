#include <stdio.h>
#include <string.h>
#include <time.h>

time_t rawtime;

char * get_time(void) {
    static char* buf;
    static char* pch;
    
    time (&rawtime);
    buf = ctime(&rawtime);
    pch = strstr (buf,"\n");
    *pch = '\0';
    
    return buf;
}

int main() {
    printf("%s test",get_time());
    return 0;
}