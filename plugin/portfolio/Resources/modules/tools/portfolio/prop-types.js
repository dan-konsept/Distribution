import {PropTypes as T} from 'prop-types'

import {User as UserType} from '#/main/core/user/prop-types'

const Portfolio = {
  propTypes: {
    id: T.string,
    title: T.string,
    meta: T.shape({
      slug: T.string,
      owner: T.shape(UserType.propTypes),
      visibility: T.string
    })
  }
}

export {
  Portfolio
}