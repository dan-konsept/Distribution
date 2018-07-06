import {makeReducer} from '#/main/core/scaffolding/reducer'
import {makeFormReducer} from '#/main/core/data/form/reducer'
import {makeListReducer} from '#/main/core/data/list/reducer'
import {FORM_SUBMIT_SUCCESS} from '#/main/core/data/form/actions'
import cloneDeep from 'lodash/cloneDeep'
import {
  INIT_DATALIST,
  POST_LOAD, 
  POST_DELETE,
  POST_RESET, 
  POST_UPDATE_PUBLICATION
} from '#/plugin/blog/resources/blog/post/store/actions'
import {
  CREATE_POST_COMMENT, 
  UPDATE_POST_COMMENT, 
  DELETE_POST_COMMENT
} from '#/plugin/blog/resources/blog/comment/store/actions'

const reducer = {
  posts: makeListReducer('posts', {
    sortBy: {    
      property: 'publicationDate',
      direction: -1
    }
  },{
    invalidated: makeReducer(false, {
      [FORM_SUBMIT_SUCCESS+'/post_edit']: () => true,
      [FORM_SUBMIT_SUCCESS+'/blog.data.options']: () => true,
      [POST_UPDATE_PUBLICATION]: () => true,
      [INIT_DATALIST]: () => true,
      [POST_DELETE]: () => true
    })
  },{
    selectable: false
  }),
  post: makeReducer({}, {
    [POST_LOAD]: (state, action) => action.post,
    [POST_UPDATE_PUBLICATION]: (state, action) => action.post,
    [POST_RESET]: () => ({}),
    [UPDATE_POST_COMMENT]: (state, action) => {
      const post = cloneDeep(state)
      const commentIndex = post.comments.findIndex(e => e.id === action.comment.id)
      post.comments[commentIndex] = action.comment
      return post
    },
    [CREATE_POST_COMMENT]: (state, action) => {
      const post = cloneDeep(state)
      post.comments.unshift(action.comment)
      return post
    },
    [DELETE_POST_COMMENT]: (state, action) => {
      const post = cloneDeep(state)
      const commentIndex = post.comments.findIndex(e => e.id === action.commentId)
      post.comments.splice(commentIndex, 1)
      return post
    }
  }),
  post_edit: makeFormReducer('post_edit'/*, {}, {
    data: makeReducer({}, {
      [FORM_UPDATE_PROP+'/post_edit']: (state, action) => {
        const data = cloneDeep(state)
        var array = action.propValue.split(",").map(item => item.trim())
        if(action.propName === 'tags'){
          console.log(array)
        }
        return data
      }
    })
  }*/)
}

export {
  reducer
}