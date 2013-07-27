#!/usr/bin/python

CA_CER = '''
-----BEGIN CERTIFICATE-----
MIICUjCCAbugAwIBAgIJANqd0HpLjwClMA0GCSqGSIb3DQEBBQUAMEIxCzAJBgNV
BAYTAlVTMRMwEQYDVQQIDApTb21lLVN0YXRlMQ4wDAYDVQQKDAVXZWJJRDEOMAwG
A1UEAwwFV2ViSUQwHhcNMTIxMTI5MjAyNjE5WhcNMTUxMTI5MjAyNjE5WjBCMQsw
CQYDVQQGEwJVUzETMBEGA1UECAwKU29tZS1TdGF0ZTEOMAwGA1UECgwFV2ViSUQx
DjAMBgNVBAMMBVdlYklEMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC2Gn0j
5CiJ+UOZCl0HYt6lxwGKh8O2ocxOU3lv4JNAB/fcJBPoMh0G/NxbFayT6W6IEF87
2TxutCtc+NECHMBfhISRF6tBpnM7ibuRlGNxCjzaH+le/NsWu/+oMUceYI5UHWbL
wj2QwQY7hi9sMKlaEOxsJKdObGhvSxo6C5tNHQIDAQABo1AwTjAdBgNVHQ4EFgQU
PeiGwB03YrZL1o9JV66Y7fia1IIwHwYDVR0jBBgwFoAUPeiGwB03YrZL1o9JV66Y
7fia1IIwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQUFAAOBgQB/FSnw2M3TExe6
cwxoGbRI9QM8cK5Uwc3FHUsikQIZyPyq+65pyGw+dRR2jft7fqb/2PIYtsrQjY5V
X1vkIaKUbUOgBF8FjnwsYV3ZqB5MfYRz9Pv2KnTwOHb3Lk84OrYyJY0yv4aLAiqA
pkMUumu6GgjM421AWxUoiibL2NQd+g==
-----END CERTIFICATE-----
'''

CA_KEY = '''
-----BEGIN RSA PRIVATE KEY-----
MIICXgIBAAKBgQC2Gn0j5CiJ+UOZCl0HYt6lxwGKh8O2ocxOU3lv4JNAB/fcJBPo
Mh0G/NxbFayT6W6IEF872TxutCtc+NECHMBfhISRF6tBpnM7ibuRlGNxCjzaH+le
/NsWu/+oMUceYI5UHWbLwj2QwQY7hi9sMKlaEOxsJKdObGhvSxo6C5tNHQIDAQAB
AoGBAI2px6YvLRZAskSRGlPPp1MhoGJLQYaHEe/w4iyVcRfD2x8HdfERYyF3fljh
YJLkapcw3VUqpuecA4vdCINzKHcV0hzQLYqy3FpiVH2sOUD/x+lCZpfCR+LBhKbJ
oVtFaJ/WrvdqPIcAmmndT8ROW/FsT8HdgSTtrreq2q0pqSYBAkEA4eTM8+RKX8rV
kNn6odTKXRQSvPl/LS9gUNAQvn9mhboUEjILE1a8JAxRKgWeqJk/+rxpZkrLIVx/
DgXhNBlhnQJBAM5fnwLM+NShF1SZvsCr0Y7XW8s9ZFqFYULAo8tVsga5xY7gBefU
B5atbdBDH0CRklwyyBVrmYIXeb+gRR3VgYECQQCWZC3Pcn3RqMjm1zj15SWVMngH
OFRjUNde9icvrMIk5e3W71pQVb6MgWbIA1XOCbl+xVGmuMTkQSCVxXRZq0xBAkEA
tmm+23Lh3tAlFxpuiU9WA7RH5vV05q5OsfokzW4J1fgOr6NElQ3NR1o0Xol17lS9
0dDxGj3pihvF+aNodF5sAQJAMuRVMUEX+1lqF8jGM+hv6IMrQi6zUdxr1QUPk2Ro
UbshjTjL5qWr+1Q3KgI8cjkhf+r6tffjcGF+5g9ODxaXsg==
-----END RSA PRIVATE KEY-----
'''

from OpenSSL import crypto
import time, os
from tempfile import mkstemp
from subprocess import call, Popen, STDOUT, PIPE
import base64

X509_TRUST_COMPAT       = 1
X509_TRUST_SSL_CLIENT   = 2
X509_TRUST_SSL_SERVER   = 3
X509_TRUST_EMAIL        = 4
X509_TRUST_OBJECT_SIGN  = 5
X509_TRUST_OCSP_SIGN    = 6
X509_TRUST_OCSP_REQUEST = 7
X509v3_KU_DIGITAL_SIGNATURE = 0x0080
X509v3_KU_NON_REPUDIATION   = 0x0040
X509v3_KU_KEY_ENCIPHERMENT  = 0x0020
X509v3_KU_DATA_ENCIPHERMENT = 0x0010
X509v3_KU_KEY_AGREEMENT     = 0x0008
X509v3_KU_KEY_CERT_SIGN     = 0x0004
X509v3_KU_CRL_SIGN          = 0x0002
X509v3_KU_ENCIPHER_ONLY     = 0x0001
X509v3_KU_DECIPHER_ONLY     = 0x8000
X509v3_KU_UNDEF             = 0xffff


CLIENT_EXTENSIONS = (
    crypto.X509Extension('basicConstraints', False, 'CA:FALSE'),
)

def _next_serial_number():
    return int(time.time()) - 1217659659

def asn1date(asn1str):
    return time.strptime(asn1str.strip('Z'), '%Y%m%d%H%M%S')

def asn1time(asn1str):
    return time.mktime(asn1date(asn1str))

def x509_data(x509):
    x509 = crypto.load_certificate(crypto.FILETYPE_PEM, x509)
    return {'subject': dict(x509.get_subject().get_components()),
            'issuer':  dict(x509.get_issuer().get_components()),
            'serial': x509.get_serial_number(),
            'notBefore': asn1date(x509.get_notBefore()),
            'notAfter': asn1date(x509.get_notAfter()),
            'sha1': x509.digest('sha1')}

def get_UID_DC(userid):
    if userid and len(userid):
        m = userid.partition('@')
        if m[1] == '@':
            DC = m[2].split('.')
            if len(DC):
                DC.reverse()
            UID = m[0]
            return UID, DC

def sign(pkey, p_ca_pem, p_ca_key, commonName, days, emailAddress=None, altName=None, userid=None):
    serial = _next_serial_number()

    ca_pem = p_ca_pem and file(p_ca_pem).read() or CA_CER
    ca_x509 = crypto.load_certificate(crypto.FILETYPE_PEM, ca_pem)
    ca_subj = ca_x509.get_subject()

    ca_pkey = p_ca_key and file(p_ca_key).read() or CA_KEY
    ca_key = crypto.load_privatekey(crypto.FILETYPE_PEM, ca_pkey)

    # create client X509 certificate
    x509 = crypto.X509()
    # 0x0:v1, 0x1:v2, 0x2:v3
    x509.set_version(2)
    x509.set_serial_number(serial)
    x509.set_issuer(ca_subj)

    # overload CAs subject and add extensions
    subj = crypto.X509Name(ca_subj)
    subj.CN = commonName

    x509.set_subject(subj)
    x509.add_extensions(CLIENT_EXTENSIONS)
    if altName:
        x509.add_extensions((
            crypto.X509Extension('subjectAltName', False, 'URI:' + altName),
        ))

    # x509 validity starts at present
    x509.gmtime_adj_notBefore(0)
    # and ends before CA expires
    x509.gmtime_adj_notAfter(days*24*3600)
    #if asn1time(x509.get_notAfter()) > asn1time(ca_x509.get_notAfter()):
    #    x509.set_notAfter(ca_x509.get_notAfter())

    # insert public key
    x509.set_pubkey(pkey)

    # add identifiers
    x509.add_extensions((
        crypto.X509Extension('subjectKeyIdentifier', False, 'hash', subject=x509),
        crypto.X509Extension('authorityKeyIdentifier', False, 'keyid', issuer=ca_x509),
    ))

    # sign with CA private key
    x509.sign(ca_key, 'sha1')
    return x509

def sign_spkac(spki, commonName, days, emailAddress=None, altName=None, userid=None):
    spki = crypto.NetscapeSPKI(spki)
    x509 = sign(spki.get_pubkey(), None, None, commonName, days, emailAddress, altName, userid)
    return crypto.dump_certificate(crypto.FILETYPE_PEM, x509)

def sign_req(pem, p_ca_pem, p_ca_key, commonName, days, emailAddress=None, altName=None, userid=None):
    x509 = crypto.load_certificate_request(crypto.FILETYPE_PEM, pem)
    pkey = x509.get_pubkey()
    x509 = sign(pkey, p_ca_pem, p_ca_key, commonName, days, emailAddress, altName, userid)
    return crypto.dump_certificate(crypto.FILETYPE_PEM, x509)

def sign_new(p_ca_pem, p_ca_key, commonName, days, emailAddress=None, altName=None, userid=None):
    pkey = crypto.PKey()
    pkey.generate_key(crypto.TYPE_RSA, 1024)
    x509 = sign(pkey, p_ca_pem, p_ca_key, commonName, days, emailAddress, altName, userid)
    return crypto.dump_privatekey(crypto.FILETYPE_PEM, pkey) + crypto.dump_certificate(crypto.FILETYPE_PEM, x509)

def x509_pkcs7(pem):
    t = mkstemp()
    os.write(t[0], pem)
    os.close(t[0])
    call(['openssl','crl2pkcs7','-nocrl','-certfile',t[1],'-out',t[1]])
    r = ''.join(file(t[1]).read().strip().split('\n')[1:-1])
    os.unlink(t[1])
    return r

def x509_pkcs12(pem, password):
    p_ssl = Popen(['openssl','pkcs12','-export','-passout','pass:%s' % password], stdin=PIPE, stdout=PIPE)
    p_ssl.stdin.write(pem)
    p_ssl.stdin.close()
    p_ssl.wait()
    return base64.encodestring(p_ssl.stdout.read())

if __name__ == '__main__':
    import argparse
    parser = argparse.ArgumentParser(description='Create and sign a WebID certificate based on a Netscape SPKAC request (using the KEYGEN element in HTML).')

    parser.add_argument('-s', '--spkac', help="Netscape SPKAC public key", required=True)
    parser.add_argument('-n', '--name', help="Certificate commonName value", required=True)
    parser.add_argument('-w', '--webid', help="WebID URI (subjectAltName)", required=True)
    parser.add_argument('-d', '--days', help="Certificate validity (in days)", default=360)

    args = parser.parse_args()
    print sign_spkac(args.spkac, args.name, args.days, altName=args.webid),

