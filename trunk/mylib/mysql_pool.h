#ifndef __MYSQL_POOL_H__
#define __MYSQL_POOL_H__

#include "c_mysql_iface.h" 
#include <map>
#include <string>

using std::map;
using std::string;

class mysql_t
{
public:
    mysql_t():mysql_conn()
    {
        index = 1;
    };
    int index;
    c_mysql_iface mysql_conn;
};

class mysql_pool
{
public:
    mysql_pool();
    ~mysql_pool();
    //return 0: 成功, -1 失败，-2 注册的key 已有了
    int init_add_pool(int key, const char* host, unsigned short port, const char *db_name, const char *user, const char *passwd, const char* charset); 
    //return 0: 成功, 1 没有找到该key
    int del_conn(int key);
    c_mysql_iface *get_mysql(int key);
    void clear();
private:
    map<int, mysql_t*> m_conn;
    map<string, mysql_t> m_pool;
};

#endif
