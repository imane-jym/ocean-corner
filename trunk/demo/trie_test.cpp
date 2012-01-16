#include "trie.h"
#include <stdio.h>
#include <strings.h>

int main(int argc, char *argv[])
{
    trie *t = new trie(); 
    t->insert("ruti");
    t->insert("uti");
    t->insert("utiy");
    char str[] = "*******ruti***rutiy************ruti****ruti****uti*****************uti***********uti**************tui*******************utiy******************";
    
    short *arr; 
    short num;
    char temp[100];
    if (t->str_search(str, &arr, &num) == 0)
    {
        int i = 0;
        for(i = 0; i < num; i++)
        {
            memset(temp, 0, 100);
            strncpy(temp, &str[arr[i * 2]], arr[i * 2 + 1]); 
            printf("%d[%d] [%d] [%s]\n", i, arr[i * 2], arr[i * 2 + 1], temp);
        }
    }
}
