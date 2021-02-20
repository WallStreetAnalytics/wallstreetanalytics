
#define FFI_SCOPE "amphp-hpack-nghttp2"
#define FFI_LIB "libnghttp2.so"

typedef struct nghttp2_hd_deflater nghttp2_hd_deflater;

typedef struct nghttp2_hd_inflater nghttp2_hd_inflater;

typedef struct {
  uint8_t *name;
  uint8_t *value;

  size_t namelen;
  size_t valuelen;

  uint8_t flags;
} nghttp2_nv;

int nghttp2_hd_deflate_new(nghttp2_hd_deflater **deflater_ptr, size_t deflate_hd_table_bufsize_max);

ssize_t nghttp2_hd_deflate_hd(nghttp2_hd_deflater *deflater, uint8_t *buf, size_t buflen, const nghttp2_nv *nva, size_t nvlen);

size_t nghttp2_hd_deflate_bound(nghttp2_hd_deflater *deflater, const nghttp2_nv *nva, size_t nvlen);

int nghttp2_hd_inflate_new(nghttp2_hd_inflater **inflater_ptr);

ssize_t nghttp2_hd_inflate_hd2(nghttp2_hd_inflater *inflater, nghttp2_nv *nv_out, int *inflate_flags, const uint8_t *in, size_t inlen, int in_final);

int nghttp2_hd_inflate_end_headers(nghttp2_hd_inflater *inflater);
