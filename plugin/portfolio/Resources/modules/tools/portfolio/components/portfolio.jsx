import React from 'react'
import {PropTypes as T} from 'prop-types'
import {connect} from 'react-redux'

import {trans} from '#/main/app/intl/translation'
import {selectors as formSelect} from '#/main/app/content/form/store'

import {Portfolio as PortfolioType} from '#/plugin/portfolio/tools/portfolio/prop-types'

const PortfolioComponent = (props) =>
  <div>
    Portfolio
  </div>

PortfolioComponent.propTypes = {
  portfolio: T.shape(PortfolioType.propTypes)
}

const Portfolio = connect(
  (state) => ({
    portfolio: formSelect.data(formSelect.form(state, 'portfolio'))
  })
)(PortfolioComponent)

export {
  Portfolio
}
