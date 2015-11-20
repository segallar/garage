#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <sys/types.h>

int main() {
	
	printf("init %d \n",getpid());

	pid_t pid;

	switch(pid=fork()) {
	case -1: 
		printf("error \n");
		exit(-1);
	case 0:
		printf("child pid %d pid var %d parent %d\n",getpid(),pid,getppid());
		exit(0);
	default:
		printf("parent pid %d child %d\n",getpid(),pid);
                exit(0);

	}
	printf("exit %d \n",getpid());
	return 0;
}
