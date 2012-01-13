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

    if (m_conn.find(key) != m_conn.end())
    {
        return -2;
    }

    map<string, mysql_t>::iterator it = m_pool.find(temp);
    if (it == m_pool.end())
    {
        m_pool[temp];
        if (0 != m_pool[temp].mysql_conn.init(host, port, db_name, user, passwd, charset))
        {
            m_pool.erase(temp);
            return -1;
        }
    }


    m_conn[key] = &(it->second);
    (it->second).index++;
    return 0;
}

int mysql_pool::del_conn(int key)
{
    map<int, mysql_t* >::iterator it = m_conn.find(key);
    if (it != m_conn.end())
    {
        it->second->index -= 1;
        m_conn.erase(it);
        map<string, mysql_t>::iterator it = m_pool.begin();
        map<string, mysql_t>::iterator it_temp;
        while(it != m_pool.end())
        {
            if((it->second).index == 0)
            {
                it_temp = it; 
                it++;
                m_pool.erase(it_temp);
            }
            else
            {
                it++;
            }
        }
        return 0;
    }
    else
    {
        return 1;
    }
}

c_mysql_iface *mysql_pool::get_mysql(int key)
{
    return &m_conn[key]->mysql_conn;
}

void mysql_pool::clear()
{
    m_conn.clear();
    m_pool.clear();
}
