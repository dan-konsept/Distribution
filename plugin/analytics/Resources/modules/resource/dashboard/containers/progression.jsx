import {connect} from 'react-redux'

import {selectors as pathSelectors} from '#/plugin/path/resources/path/store'
import {Progression as ProgressionComponent} from '#/plugin/analytics/resource/dashboard/components/progression'

const Progression = connect(
  (state) => ({
    path: pathSelectors.path(state)
  })
)(ProgressionComponent)

export {
  Progression
}
