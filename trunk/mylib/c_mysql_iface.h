
#ifndef __MYSQL_IFACE_H__
#define __MYSQL_IFACE_H__

#include <stdarg.h>
#include <stdio.h>

#include <mysql/mysql.h>

#define MYSQL_MAX_ERROR_BUF_LEN 1024

#define MYSQL_MAX_HOST_BUF_LEN 128
#define MYSQL_INIT_SQL_BUF_LEN (1024 * 1024)

#define MYSQL_IFACE_E_SUCCESS 0
#define MYSQL_IFACE_E_MYSQL_API 1
#define MYSQL_IFACE_E_NOT_INITED 2
#define MYSQL_IFACE_E_PARAMETER 3
#define MYSQL_IFACE_E_NULL_FIELD 4
#define MYSQL_IFACE_E_ILLEGAL_CALL 5


/**
 * @class c_mysql_iface
 * @brief mysql接口的实现类
 */
class c_mysql_iface
{
    public:
        c_mysql_iface();
        ~c_mysql_iface();

        /**
         * @brief 初始化接口
         *
         * @param host 数据库主机名称
         * @param port 数据库主机端口
         * @param db_name 数据库名称
         * @param user 数据库用户名
         * @param passwd 数据库密码
         * @param charset 连接数据库时试用的字符集
         *
         * @return 0 成功; <0 失败
         */
        int init(const char* host, unsigned short port, const char* db_name, const char* user, const char* passwd, const char* charset);
        /**
         * @brief 反初始化接口
         *
         * @return 0 成功; <0 失败
         */
        int uninit();
        /**
         * @brief 获取 Mysql 指针
         *
         * @return !=NULL 成功; =NULL 失败
         */
        MYSQL* get_conn();

        /**
         * @brief 组装sql语句 初始化函数 
         * @note 每次要使用sql_add,sql_escape_add, get_sql时候都要首先调用 
         */
        void init_sql();
        /**
         * @brief 组装sql语句 不进行mysql_escape转义, 用于处理c string, 只是简单的把buffer添加到 当前的sql buffer后面 
         *
         * @param buffer sql 格式为以'\0'结尾的字符串
         * @return 0 成功； -1 失败
         */
        int sql_add(char *buffer);
        /**
         * @brief 组装sql语句 进行mysql_escape转义 把buffer里的string 变成 转义的mysql string, 再添加到当前 sql buffer 后面 
         *
         * @param buffer sql 可以是二进制数据 内部可以含有'\0' mysql特殊字符
         * @param length sql语句长度
         * @return 0 成功； -1 失败
         */
        int sql_escape_add(char *buffer, unsigned long length);

        /**
         * @brief 获取sql语句指针 
         */
        char *get_sql();

        /**
         * @brief 执行指定的 SELECT 等查询语句，并返回第一行的结果 用于执行格式化sql  
         *
         * @param row 用于保存返回的第一行
         * @param length 用于存放第一行字段的数据长度 不需要置NULL
         * @param sql_fmt SQL 语句的可变参格式字符串
         *
         * @note 这个函数用于执行需要获取数据的 SQL 语句，类似 UPDATE 及 DELETE 等更新语句请试用 execsql 函数
         *
         * @return >=0 满足 SQL 查询的记录条数; <0 失败
         */
        int select_first_row_f(MYSQL_ROW* row, unsigned long **length, const char* sql_fmt, ...);
        /**
         * @brief 执行指定的 SELECT 等查询语句，并返回第一行的结果 用于执行sql_buffer里的sql语句  
         *
         * @param row 用于保存返回的第一行
         * @param length 用于存放第一行字段的数据长度 不需要置NULL
         * @param sql_buffer SQL 语句
         *
         * @note 这个函数用于执行需要获取数据的 SQL 语句，类似 UPDATE 及 DELETE 等更新语句请试用 execsql 函数
         *
         * @return >=0 满足 SQL 查询的记录条数; <0 失败
         */
        int select_first_row(MYSQL_ROW* row, unsigned long **length, const char* sql_buffer);

        /**
         * @brief 用于执行完 select_first_row* 以后，获取下一行数据
         *
         * @param row 用于保存返回的第一行
         * @param length 用于存放第一行字段的数据长度 不需要置NULL
         *
         * @return 0 行数据; 1 没有可以获取的行; -1 错误
         */
        int select_next_row(MYSQL_ROW* row, unsigned long **length = NULL);

        /**
         * @brief 执行指定的 UPDATE 或 DELETE 等更新语句，并返回影响的行数
         *
         * @param sql_fmt SQL 语句的可变参格式字符串
         *
         * @note 这个函数用于执行UPDATE 及 DELETE 等更新语句，类似 SELECT 等需要获取数据的情况，请试用 select_first_row 函数
         *
         * @return >=0 更新语句影响的行数; <0 失败
         */
        int execsql(const char* sql_fmt, ...);

        /**
         * @brief 获取最后一次操作的错误码
         *
         * @return 最后一次操作的错误码
         */
        int get_last_errno() const;

        /**
         * @brief 获取最后一次操作的错误字符串
         *
         * @return 最后一次操作的错误字符串
         */
        const char* get_last_errstr() const;

private:
    void set_err(int errno, const char* msg, ...);

    MYSQL* m_hdb; /**< @brief 对象内部维护的MYSQL连接指针 */
    MYSQL_RES* m_res; /**< @brief  维护select_first_row和select_next_row查询结果的内部资源*/

    char m_host[MYSQL_MAX_HOST_BUF_LEN]; /**< @brief 数据库主机地址 */
    unsigned short m_port; /**< @brief 数据库主机端口 */

    char *m_sql_buffer; /**< @brief  接保存接口串入的SQL语句的缓存*/

    int m_errno; /**< @brief 保存本对象错误号 */

    char m_errstr[MYSQL_MAX_ERROR_BUF_LEN]; /**< @brief 保存本对象的错误描述 */
    unsigned int m_sql_length;
    unsigned int m_sql_length_limit;
};

#endif // !__MYSQL_IFACE_H__

