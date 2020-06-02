<?php
/*
 * Copyright (C) 2017  Florian HENRY <florian.henry@open-concept.pro>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file class/TCPDFAgefodd.class.php
 * \ingroup agefodd
 * \brief This file is an example hook overload class file
 * Put some comments here
 */

require_once TCPDF_PATH.'tcpdf.php';

/**
 * Class TCPDFAgefodd
 */
class TCPDFAgefodd extends TCPDF
{
	public $outputlangs;
	public $model;

	//Page header
	public function Header() {
		$this->model->_pagehead($this->ref_object, 1, $this->model->outputlangs);
	}

	// Page footer
	public function Footer() {
		$this->model->_pagefoot($this->ref_object, $this->model->outputlangs);
	}

}
