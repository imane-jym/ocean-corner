#include "mysql_pool.h"
#include <stdio.h>
#include <strings.h>

int main(int argc, char *argv[])
{
    mysql_pool dbconn_pool;
    dbconn_pool->init_add_pool(1,'10.1.1.44', 3306, 'db_stat_config', 'imane', 'imane', 'utf8');
    dbconn_pool->init_add_pool(2,'10.1.1.44', 3306, 'db_stat_config', 'imane', 'imane', 'utf8');
    dbconn_pool->del_conn(2);
    c_mysql_iface *p_mysql = dbconn_pool->get_mysql(1);
    MYSQL_ROW row;
    p_mysql->select_first_row_f(&row, NULL, "select * from t_test_2");
    return 0;
}
