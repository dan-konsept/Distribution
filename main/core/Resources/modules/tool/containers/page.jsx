import {connect} from 'react-redux'

import {withReducer} from '#/main/app/store/components/withReducer'

import {ToolPage as ToolPageComponent} from '#/main/core/tool/components/page'
import {reducer, selectors} from '#/main/core/tool/store'

const ToolPage = withReducer(selectors.STORE_NAME, reducer)(
  connect(
    (state) => ({
      //loaded: selectors.loaded(state),
      name: selectors.name(state),
      currentContext: selectors.context(state)
    }),
    undefined,
    undefined,
    {
      areStatesEqual: (next, prev) => selectors.store(prev) === selectors.store(next)
    }
  )(ToolPageComponent)
)

export {
  ToolPage
}
