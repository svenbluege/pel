<?php

/*  PEL: PHP EXIF Library.  A library with support for reading and
 *  writing all EXIF headers of JPEG images using PHP.
 *
 *  Copyright (C) 2004  Martin Geisler <gimpster@users.sourceforge.net>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program in the file COPYING; if not, write to the
 *  Free Software Foundation, Inc., 59 Temple Place, Suite 330,
 *  Boston, MA 02111-1307 USA
 */

/* $Id$ */


/**
 * Classes for dealing with EXIF data.
 *
 * @author Martin Geisler <gimpster@users.sourceforge.net>
 * @version $Revision$
 * @date $Date$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public
 * License (GPL)
 * @package PEL
 * @subpackage EXIF
 */

/** Class definition of {@link PelException}. */
require_once('PelException.php');
/** Class definition of {@link PelJpegContent}. */
require_once('PelJpegContent.php');
/** Class definition of {@link PelIfd}. */
require_once('PelIfd.php');
/** Class definition of {@link PelTag}. */
require_once('PelTag.php');
/** Class definition of {@link PelEntry}. */
require_once('PelEntry.php');
/** Class definition of {@link PelFormat}. */
require_once('PelFormat.php');

/**
 * Exception throw if invalid EXIF data is found.
 *
 * @author Martin Geisler <gimpster@users.sourceforge.net>
 * @package PEL
 * @subpackage Exception
 */
class PelExifInvalidDataException extends PelException {}

/**
 * Class representing EXIF data.
 *
 * EXIF data resides as {@link PelJpeg data} in a {@link
 * PelJpegSection JPEG section} and consists of a header followed by a
 * number of {@link PelJpegIfd IFDs}.
 *
 * @author Martin Geisler <gimpster@users.sourceforge.net>
 * @package PEL
 * @subpackage EXIF
 */
class PelExifData extends PelJpegContent {

  /**
   * EXIF header.
   *
   * The EXIF data must start with these six bytes to be considered
   * valid.
   */
  const EXIF_HEADER = "Exif\0\0";

  /**
   * TIFF header.
   *
   * Following the {@link EXIF_HEADER EXIF header} and the two bytes
   * indicating the byte order, one must find this value.
   */
  const TIFF_HEADER = 0x002A;

  /* The first PelIfd, if any */
  private $ifd = null;
  private $size = 0;

  private $order = PelConvert::LITTLE_ENDIAN;


  function __construct(PelDataWindow $d) {
    //printf("Parsing %d bytes of EXIF data...\n", $d->getSize());
    $this->size = $d->getSize();

    if ($d->getSize() < 6)
      throw new PelExifInvalidDataException('Not enough data to be ' .
                                            'valid EXIF data.');
    
//     if ($d->strcmp(0, self::EXIF_HEADER)) {
//       printf ("Found EXIF header.\n");
//     } else {
//       while (true) {
//         while ((ord($d{0}) == 0xFF) && $size > 0) {
//           $d = substr($d, 1);
//           $size--;
//         }

//         /* JPEG_MARKER_SOI */
//         if (ord($d{0}) == PelJpegMarker::SOI) {
//           printf("Found PelJpegMarker::SOI\n");
//           $d = substr($d, 1);
//           $size--;
//           continue;
//         }
        
//         /* JPEG_MARKER_APP0 */
//         if (ord($d{0}) == PelJpegMarker::APP0) {
//           printf("Found PelJpegMarker::APP0\n");
//           $d = substr($d, 1);
//           $size--;
//           $l = ($d{0} << 8) | $d{1};
//           if ($l > $size)
//           throw new PelExifInvalidDataException('Invalid length: %d > %d',
//                                                 $l, $size);
//           $d = substr($d, $l);
//           $size -= $l;
//           continue;
//         }
        
//         /* JPEG_MARKER_APP1 */
//         if (ord($d{0}) == PelJpegMarker::APP1) {
//           printf("Found PelJpegMarker::APP1\n");
//           break;
//         }        
//         /* Unknown marker or data. Give up. */
//         throw new PelExifInvalidDataException('EXIF marker not found.');
//     }
    
//     $d->setWindowStart(1);
// $d = substr($d, 1);
//       $size--;
//       if ($size < 2) {
//         throw new PelExifInvalidDataException('Size too small.');
//       }
//       $len = (ord($d{0}) << 8) | ord($d{1});
//       printf ("We have to deal with %d bytes of EXIF data.\n", $len);
//       $d = substr($d, 2);
//       $size -= 2;
//       throw new PelExifInvalidDataException('EXIF marker not found.');
//     }

    /* There must be at least 14 bytes available: 6 bytes for the EXIF
     * header, 2 bytes for the byte order, 2 bytes for the TIFF
     * header, and 4 bytes for the offset to the first IFD. */
    if ($d->getSize() < 14)
      throw new PelExifInvalidDataException('Not enough data to be ' .
                                            'valid EXIF data.');
    
    /* Verify the EXIF header */
    if ($d->strcmp(0, self::EXIF_HEADER)) {
      //printf ("Found EXIF header.\n");
    } else {
      throw new PelExifInvalidDataException('EXIF header not found.');
    }
    
    /* Byte order */
    if ($d->strcmp(6, 'II')) {
      $d->setByteOrder(PelConvert::LITTLE_ENDIAN);
      $this->order = PelConvert::LITTLE_ENDIAN;
      Pel::debug('Found Intel byte order');
    } elseif ($d->strcmp(6, 'MM')) {
      $d->setByteOrder(PelConvert::BIG_ENDIAN);
      $this->order = PelConvert::LITTLE_ENDIAN;
      Pel::debug('Found Motorola byte order');
    } else {
      throw new PelExifInvalidDataException('Unknown byte order: 0x%2X%2X',
                                            $d->getByte(6), $d->getByte(7));
    }
    
    /* Verify the TIFF header */
    if ($d->getShort(8) != self::TIFF_HEADER)
      throw new PelExifInvalidDataException('Missing TIFF magic value.');

    /* IFD 0 offset */
    $offset = $d->getLong(10);
    //printf ("IFD 0 at %d.\n", $offset);

    /* Parse the actual exif data, the Ifd needs to know it's own
     * offset within the APP1 marker so that it can calculate proper
     * offsets to other Ifds. */
    /* The offset counts from the beginning of the TIFF header, which
     * itself starts with the 'II' or 'MM' at byte 6. */
    $this->ifd = new PelIfd($d->getClone(6), $offset);
  }

  function getSize() {
    return $this->size;
  }

  function getIfd() {
    return $this->ifd;
  }



  function getBytes() {
    $bytes = self::EXIF_HEADER;

    if ($this->order == PelConvert::LITTLE_ENDIAN)
      $bytes .= 'II';
    else
      $bytes .= 'MM';
    
    /* TIFF magic number --- fixed value. */
    $bytes .= PelConvert::shortToBytes(0x002A, $this->order);

    /*
     * IFD 0 offset.  We will always start IDF 0 after the EXIF header
     * at an offset of 8 bytes (2 bytes for byte order, another 2
     * bytes for the TIFF header, and 4 bytes for the IFD 0 offset
     * make 8 bytes together).
     */
    $bytes .= PelConvert::longToBytes(8, $this->order);

    /* Now save IFD 0. IFD 1 will be saved automatically. */
    //printf ("Saving IFDs...\n");

    /* The argument specifies the offset within the EXIF data of this
     * IFD.  The IFD will use this to calculate offsets from the EXIF
     * entries to their data, all those offsets are absolute offsets
     * counted from the beginning of the EXIF data. */
    $bytes .= $this->ifd->getBytes(8, $this->order);

    return $bytes;
  }


  function __toString() {

    $str = '';

    if ($this->ifd != null)
      $str .= $this->ifd->__toString();
    
    return $str;
  }

}



?>