import {makeReducer} from '#/main/app/store/reducer'
import {makeListReducer} from '#/main/app/content/list/store'
import {makeFormReducer} from '#/main/app/content/form/store/reducer'
import {FORM_SUBMIT_SUCCESS} from '#/main/app/content/form/store/actions'

const reducer = {
  portfolios: makeListReducer('portfolios', {}, {
    invalidated: makeReducer(false, {
      [FORM_SUBMIT_SUCCESS+'/portfolio']: () => true
    })
  }),
  portfolio: makeFormReducer('portfolio')
}

export {
  reducer
}