import React from 'react'

import mainLogo from '../../assets/cover1.jpg'
import styles from './index.css'

const IndexPage = () => {
    return (
        <div className="mainLogoDiv">
            <img src={mainLogo}  className="mainLogoImg"/>
        </div>
    )
}

export default IndexPage