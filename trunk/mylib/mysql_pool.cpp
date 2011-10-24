#include "mysql_pool.h" 
#include <stdio.h>

mysql_pool::mysql_pool()
{
}

mysql_pool::~mysql_pool()
{
}

int mysql_pool::init_add_pool(int key, const char* host, unsigned short port, const char *db_name, const char *user, const char *passwd, const char* charset)
{
    string temp;
    char buffer[300];
    sprintf(buffer, "%s_%u_%s_%s_%s", host, port, db_name, user, passwd);
    temp = buffer;
    map<string, mysql_t>::iterator it = m_pool.find(temp);
    if (it == m_pool.end())
    {
        c_mysql_iface a;
        m_pool[temp];
        if (0 != m_pool[temp].mysql_conn.init(host, port, db_name, user, passwd, charset))
        {
            m_pool.erase(temp);
            return -1;
        }
        m_conn[key] = &m_pool[temp];
        return 0;
    }
    else
    {
        m_conn[key] = &(it->second);
        return 0;
    }
}

c_mysql_iface *mysql_pool::get_mysql(int key)
{
    return m_conn[key];
}

void mysql_pool::clear()
{
    m_conn.clear();
    m_pool.clear();
}
