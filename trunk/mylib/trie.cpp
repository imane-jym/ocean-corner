/**
 * =====================================================================================
 *       @file  trie.cpp
 *      @brief  
 *
 *  Detailed description starts here.
 *
 *   @internal
 *     Created  07/22/2011 10:11:11 AM 
 *    Revision  1.0.0.0
 *    Compiler  gcc/g++
 *     Company  TaoMee.Inc, ShangHai.
 *   Copyright  Copyright (c) 2011, TaoMee.Inc, ShangHai.
 *
 *     @author imane 
 * This source code was wrote for TaoMee,Inc. ShangHai CN.
 * =====================================================================================
 */

#include "trie.h"
#include <cstdio>

trie::trie()
{
    m_root = NULL;
    m_state = NULL;
    m_location_mark = NULL;
    m_word_max_length = 0;
}

trie::~trie()
{
    if (m_state != NULL)
    {
        free(m_state);
        m_state = NULL;
    }
    if (m_root != NULL)
    {
        destroy(m_root); 
        m_root = NULL;
    }
    if (m_location_mark != NULL)
    {
        free(m_location_mark);
        m_location_mark = NULL;
    }
    m_word_max_length = 0;
}

void trie::destroy(trie_node *root)
{
    int i = 0;
    for (i = 0; i < branchnum; i++)
    {
        if (root->next[i] != NULL)
        {
            destroy(root->next[i]);
        }
    }
    free(root);
}

bool trie::insert(const char *word)
{
    if (word == NULL || *word == '\0')
    {
        return true;
    }

    if (m_root == NULL)
    {
        m_root = (trie_node *)malloc(sizeof(trie_node)); 
        if (m_root == NULL)
        {
            return false;
        }
        m_root->is_str = false;
        memset(m_root->next, 0, sizeof(m_root->next));
    }

    int length = strlen(word);
    if (m_word_max_length == 0)
    { 
        if (length <= 10)
        {
            m_word_max_length = 10;
            m_state = (match_state *)malloc(sizeof(match_state) * m_word_max_length);
            if (m_state == NULL)
            {
                return false;
            }
            memset(m_state, 0, sizeof(match_state) * m_word_max_length);

            m_location_mark = (short *)malloc(sizeof(short) * m_word_max_length);
            if (m_location_mark == NULL)
            {
                return false;
            }
            memset(m_location_mark, 0, sizeof(short) * m_word_max_length);
        }
        else
        {
            m_word_max_length = length;
            m_state = (match_state *)malloc(sizeof(match_state) * m_word_max_length);
            if (m_state == NULL)
            {
                return false;
            }
            memset(m_state, 0, sizeof(match_state) * m_word_max_length);

            m_location_mark = (short *)malloc(sizeof(short) * m_word_max_length);
            if (m_location_mark == NULL)
            {
                return false;
            }
            memset(m_location_mark, 0, sizeof(short) * m_word_max_length);
        }
    }
    else if (length > m_word_max_length)
    {
        m_word_max_length = length;
        m_state = (match_state *)realloc(m_state, m_word_max_length * sizeof(match_state)); 
        if (m_state == NULL)
        {
            return false;
        }
        memset(m_state, 0, sizeof(match_state) * m_word_max_length);

        m_location_mark = (short *)realloc(m_location_mark, sizeof(short) * m_word_max_length);
        if (m_location_mark == NULL)
        {
            return false;
        }
        memset(m_location_mark, 0, sizeof(short) * m_word_max_length);
    }

    trie_node *location = m_root;
    while (*word)
    {
        if (location->next[(unsigned char)*word] == NULL)
        {
            trie_node *tmp = (trie_node *)malloc(sizeof(trie_node)); 
            if (tmp == NULL)
            {
                return false;
            }
            tmp->is_str = false;
            memset(tmp->next, 0, sizeof(tmp->next));
            location->next[(unsigned char)*word] = tmp;
        } 
        location = location->next[(unsigned char)*word];
        word++;
    }
    location->is_str = true;
    return true;
}

bool trie::search(const char *word)
{
    trie_node *location = m_root;
    if (m_root == NULL)
    {
        return false;
    }
    while(*word && location)
    {
        location = location->next[(unsigned char)*word];
        word++;
    }
    return (location != NULL && location->is_str);
}

bool trie::one_char_search(const char ch, short **begin, short *number)
{
    if (m_root == NULL)
    {
        return false;
    }

    int i;
    for (i = m_word_max_length - 2; i >= 0; i--) 
    {
        if (m_state[i].flag == 1)
        {
            if (m_state[i].location->next[(unsigned char)ch] == NULL)
            {
                m_state[i+1].flag = 0;
            }
            else if (m_state[i].location->next[(unsigned char)ch]->is_str)
            {
                m_state[i+1].flag = 2;
                m_state[i+1].location = m_state[i].location->next[(unsigned char)ch];
            }
            else
            {
                m_state[i+1].flag = 1;
                m_state[i+1].location = m_state[i].location->next[(unsigned char)ch];
            }
            m_state[i].flag = 0;
        }
    }
    
    if (m_root->next[(unsigned char)ch] == NULL)
    {
        m_state[0].flag = 0;
    }
    else if (m_root->next[(unsigned char)ch]->is_str)
    {
        m_state[0].flag = 2; 
        m_state[0].location = m_root->next[(unsigned char)ch];
    }
    else
    {
        m_state[0].flag = 1;
        m_state[0].location = m_root->next[(unsigned char)ch];
    }

    int match_number = 0; 
    int j = 0;
    for(i = 0; i < m_word_max_length; i++)
    {
        if (m_state[i].flag == 2)
        {
            for(j = 0; j < branchnum; j++)
            {
                if (m_state[i].location->next[j] != NULL)
                {
                    m_state[i].flag = 1;
                    break;
                }
            }
            if (m_state[i].flag == 2)
            {
                m_state[i].flag = 0;
            }
            m_location_mark[match_number] = i;
            match_number++;
        }
    }
    
    if (begin != NULL)
    {
        *begin = m_location_mark; 
    }
    if (number != NULL)
    {
        *number = match_number;
    }
    return (match_number != 0 ? true : false);
}

void trie::end_char_search()
{
    memset(m_state, 0, sizeof(match_state) * m_word_max_length);
}
