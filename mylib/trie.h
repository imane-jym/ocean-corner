/**
 * =====================================================================================
 *       @file  trie.h
 *      @brief  
 *
 *  Detailed description starts here.
 *
 *   @internal
 *     Created  07/22/2011 10:22:18 AM 
 *    Revision  1.0.0.0
 *    Compiler  gcc/g++
 *     Company  TaoMee.Inc, ShangHai.
 *   Copyright  Copyright (c) 2011, TaoMee.Inc, ShangHai.
 *
 *     @author  imane 
 * This source code was wrote for TaoMee,Inc. ShangHai CN.
 * =====================================================================================
 */

#include <cstring>
#include <cstdlib>

const int branchnum = 256; // 对于一个字节256种情况

struct trie_node
{
    bool is_str;
    trie_node *next[branchnum];
};

struct match_state
{
    short flag;            //表明当前状态情况 0:匹配不成功  1:是子串， 2:匹配成功   
    trie_node *location;
};

class trie
{
public:
    trie();
    ~trie();
    bool insert(const char *word);
    bool search(const char *word);
    
    //number当前字符匹配的字符串数量， begin匹配的字符串长度-1的数组
    bool one_char_search(const char ch, short **begin = NULL, short *number = NULL);
    void end_char_search();
private:

    void destroy(trie_node *root);
    trie_node *m_root;
    match_state *m_state; 

    //用于存储匹配到的字符串的位置 只用于one_char_search接口
    int m_word_max_length;
    short *m_location_mark;
};
